/***************************************************************
 Copyright (C) 2010 Hewlett-Packard Development Company, L.P.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 version 2 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

 ***************************************************************/
/*
 \file buckets.c
 \brief Bucket agent

 The bucket agent uses user rules (see bucket table) to classify
 files into user categories
 */

//#define BOBG
#include "buckets.h"

int debug = 0;

/* global mimetype_pk's for Debian source and binary packages */
int DEB_SOURCE;
int DEB_BINARY;

#ifdef SVN_REV
char BuildVersion[]="Build version: " SVN_REV ".\n";
#endif /* SVN_REV */


/****************************************************/
int main(int argc, char **argv) 
{
  char *agentDesc = "Bucket agent";
  int cmdopt;
  int verbose = 0;
  int writeDB = 0;
  int ReadFromStdin = 1;
  int head_uploadtree_pk = 0;
  void *DB;   // DB object from agent
  PGconn *pgConn;
  PGresult *topresult;
  PGresult *result;
  char sqlbuf[512];
  char inbuf[64];
  char *inbufp;
  char *Delims = ",= \t\n\r";
  char *token, *saveptr;
  int agent_pk = 0;
  int nomos_agent_pk = 0;
  int bucketpool_pk = 0;
  int ars_pk = 0;
  int readnum = 0;
  int rv;
  int hasPrules;
  char *bucketpool_name;
//  int *bucketList;
  pbucketdef_t bucketDefArray = 0;
  pbucketdef_t tmpbucketDefArray = 0;
  cacheroot_t  cacheroot;
  uploadtree_t  uploadtree;

  extern int AlarmSecs;

  /* Connect to the database */
  DB = DBopen();
  if (!DB) 
  {
    printf("FATAL: Bucket agent unable to connect to database, exiting...\n");
    exit(-1);
  }
  pgConn = DBgetconn(DB);
  writeDB = 1;  /* default is to write to the db */

  /* command line options */
  while ((cmdopt = getopt(argc, argv, "din:p:t:u:v")) != -1) 
  {
    switch (cmdopt) 
    {
      case 'd': /* Debug.  Do not write results to db.
                   Note: license_ref may get written to even if writeDB=0
                   Note: Never use -d unless you are debugging and know
                         what you are doing.  Several functions
                         depend on db updates (like determining bucket
                         of container).
                 */
            writeDB = 0;
            verbose++;
            break;
      case 'i': /* "Initialize" */
            DBclose(DB); /* DB was opened above, now close it and exit */
            exit(0);
      case 'n': /* bucketpool_name  */
            ReadFromStdin = 0;
            bucketpool_name = optarg;
            /* find the highest rev active bucketpool_pk */
            if (!bucketpool_pk)
            {
              bucketpool_pk = getBucketpool_pk(pgConn, bucketpool_name);
              if (!bucketpool_pk)
                printf("%s is not an active bucketpool name.\n", bucketpool_name);
            }
            break;
      case 'p': /* bucketpool_pk */
            ReadFromStdin = 0;
            bucketpool_pk = atoi(optarg);
            /* validate bucketpool_pk */
            sprintf(sqlbuf, "select bucketpool_pk from bucketpool where bucketpool_pk=%d and active='Y'", bucketpool_pk);
            bucketpool_pk = validate_pk(pgConn, sqlbuf);
            if (!bucketpool_pk)
              printf("%d is not an active bucketpool_pk.\n", atoi(optarg));
            break;
      case 't': /* uploadtree_pk */
            ReadFromStdin = 0;
            if (uploadtree.upload_fk) break;
            head_uploadtree_pk = atoi(optarg);
            /* validate bucketpool_pk */
            sprintf(sqlbuf, "select uploadtree_pk from uploadtree where uploadtree_pk=%d", head_uploadtree_pk);
            head_uploadtree_pk = validate_pk(pgConn, sqlbuf);
            if (!head_uploadtree_pk)
              printf("%d is not an active uploadtree_pk.\n", atoi(optarg));
            break;
      case 'u': /* upload_pk */
            ReadFromStdin = 0;
            if (!head_uploadtree_pk)
            {
              uploadtree.upload_fk = atoi(optarg);
              /* validate upload_pk  and get uploadtree_pk  */
              sprintf(sqlbuf, "select upload_pk from upload where upload_pk=%d", uploadtree.upload_fk);
              uploadtree.upload_fk = validate_pk(pgConn, sqlbuf);
              if (!uploadtree.upload_fk)
                printf("%d is not an valid upload_pk.\n", atoi(optarg));
              else
              {
                sprintf(sqlbuf, "select uploadtree_pk from uploadtree where upload_fk=%d and parent is null", uploadtree.upload_fk);
                head_uploadtree_pk = validate_pk(pgConn, sqlbuf);
              }
            }
            break;
      case 'v': /* verbose output for debugging  */
            /* FOR NOW this also means debug but does write to db */
            verbose++;
            break;
      default:
            Usage(argv[0]);
            DBclose(DB);
            exit(-1);
    }
  }
  debug = verbose;

  /*** validate command line ***/
  if (!bucketpool_pk && !ReadFromStdin)
  {
    printf("FATAL: You must specify an active bucketpool.\n");
    Usage(argv[0]);
    exit(-1);
  }
  if (!head_uploadtree_pk && !ReadFromStdin)
  {
    printf("FATAL: You must specify a valid uploadtree_pk or upload_pk.\n");
    Usage(argv[0]);
    exit(-1);
  }

  /* get agent pk 
   * Note, if GetAgentKey fails, this process will exit.
   */
  agent_pk = GetAgentKey(DB, basename(argv[0]), 0, SVN_REV, agentDesc);

  /*** Initialize the license_ref table cache ***/
  /* Build the license ref cache to hold 2**11 (2048) licenses.
     This MUST be a power of 2.
   */
  cacheroot.maxnodes = 2<<11;
  cacheroot.nodes = calloc(cacheroot.maxnodes, sizeof(cachenode_t));
  if (!lrcache_init(pgConn, &cacheroot))
  {
    printf("FATAL: Bucket agent could not allocate license_ref table cache.\n");
    exit(1);
  }

  /* set the heartbeat alarm signal */
  if (writeDB)
  {
    signal(SIGALRM, ShowHeartbeat);
    alarm(AlarmSecs);
  }

  /* main processing loop */
  while(++readnum)
  {
    uploadtree.upload_fk = 0;
    if (ReadFromStdin) 
    {
      bucketpool_pk = 0;
      printf("OK\n");
      fflush(stdout);

      /* Read the bucketpool_pk and upload_pk from stdin.
       * Format looks like 'bppk=123, upk=987'
       */
      if (ReadLine(stdin, inbuf, sizeof(inbuf)) < 0) break;
      inbufp = inbuf;
      if (!inbufp) break;

      token = strtok_r(inbufp, Delims, &saveptr);
      while (token && (!uploadtree.upload_fk || !bucketpool_pk))
      {
        if (strcmp(token, "bppk") == 0)
        {
          bucketpool_pk = atoi(strtok_r(NULL, Delims, &saveptr));
        }
        else
        if (strcmp(token, "upk") == 0)
        {
          uploadtree.upload_fk = atoi(strtok_r(NULL, Delims, &saveptr));
        }
        token = strtok_r(NULL, Delims, &saveptr);
      }

      /* From the upload_pk, get the head of the uploadtree, pfile_pk and ufile_name  */
      sprintf(sqlbuf, "select uploadtree_pk, pfile_fk, ufile_name, ufile_mode,lft,rgt from uploadtree \
             where upload_fk='%d' and parent is null limit 1", uploadtree.upload_fk);
      topresult = PQexec(pgConn, sqlbuf);
      if (checkPQresult(topresult, sqlbuf, agentDesc, __LINE__)) return -1;
      if (PQntuples(topresult) == 0) 
      {
        printf("ERROR: %s.%s missing upload_pk %d.\nsql: %s", 
               __FILE__, agentDesc, uploadtree.upload_fk, sqlbuf);
        PQclear(topresult);
        continue;
      }
      head_uploadtree_pk = atol(PQgetvalue(topresult, 0, 0));
      uploadtree.uploadtree_pk = head_uploadtree_pk;
      uploadtree.upload_fk = uploadtree.upload_fk;
      uploadtree.pfile_fk = atol(PQgetvalue(topresult, 0, 1));
      uploadtree.ufile_name = strdup(PQgetvalue(topresult, 0, 2));
      uploadtree.ufile_mode = atoi(PQgetvalue(topresult, 0, 3));
      uploadtree.lft = atoi(PQgetvalue(topresult, 0, 4));
      uploadtree.rgt = atoi(PQgetvalue(topresult, 0, 5));
      PQclear(topresult);
    } /* end ReadFromStdin */
    else
    {
      /* Only one input to process if from command line, so terminate if it's been done */
      if (readnum > 1) break;

      /* not reading from stdin 
       * Get the pfile, and ufile_name for head_uploadtree_pk
       */
      sprintf(sqlbuf, "select pfile_fk, ufile_name, ufile_mode,lft,rgt, upload_fk from uploadtree where uploadtree_pk=%d", head_uploadtree_pk);
      topresult = PQexec(pgConn, sqlbuf);
      if (checkPQresult(topresult, sqlbuf, agentDesc, __LINE__)) return -1;
      if (PQntuples(topresult) == 0) 
      {
        printf("FATAL: %s.%s missing root uploadtree_pk %d\n", 
               __FILE__, agentDesc, head_uploadtree_pk);
        PQclear(topresult);
        continue;
      }
      uploadtree.uploadtree_pk = head_uploadtree_pk;
      uploadtree.pfile_fk = atol(PQgetvalue(topresult, 0, 0));
      uploadtree.ufile_name = strdup(PQgetvalue(topresult, 0, 1));
      uploadtree.ufile_mode = atoi(PQgetvalue(topresult, 0, 2));
      uploadtree.lft = atoi(PQgetvalue(topresult, 0, 3));
      uploadtree.rgt = atoi(PQgetvalue(topresult, 0, 4));
      uploadtree.upload_fk = atoi(PQgetvalue(topresult, 0, 5));
      PQclear(topresult);
    }

    /* Find the most recent nomos data for this upload.  That's what we want to use
         to process the buckets.
     */
    nomos_agent_pk = LatestNomosAgent(pgConn, uploadtree.upload_fk);
    if (nomos_agent_pk == 0)
    {
      printf("WARNING: Bucket agent called on treeitem (%d), but the latest nomos agent hasn't created any license data for this tree.\n",
            head_uploadtree_pk);
      continue;
    }

    /* at this point we know:
     * bucketpool_pk, bucket agent_pk, nomos agent_pk, upload_pk, 
     * pfile_pk, and head_uploadtree_pk (the uploadtree_pk of the head tree to scan)
     */

    /* Has the upload already been processed?  If so, we are done.
       Don't even bother to create a bucket_ars entry.
     */ 
    switch (UploadProcessed(pgConn, agent_pk, nomos_agent_pk, uploadtree.pfile_fk, head_uploadtree_pk, uploadtree.upload_fk, bucketpool_pk)) 
    {
      case 1:  /* upload has already been processed */
        printf("LOG: Duplicate request for bucket agent to process upload_pk: %d, uploadtree_pk: %d, bucketpool_pk: %d, bucket agent_pk: %d, nomos agent_pk: %d, pfile_pk: %d ignored.\n",
             uploadtree.upload_fk, head_uploadtree_pk, bucketpool_pk, agent_pk, nomos_agent_pk, uploadtree.pfile_fk);
        continue;
      case -1: /* SQL error, UploadProcessed() wrote error message */
        continue; 
      case 0:  /* upload has not been processed */
        break;
    }

    /*** Initialize the Bucket Definition List bucketDefArray  ***/
    bucketDefArray = initBuckets(pgConn, bucketpool_pk, &cacheroot);
    if (bucketDefArray == 0)
    {
      printf("FATAL: %s.%d Bucket definition for pool %d could not be initialized.\n",
             __FILE__, __LINE__, bucketpool_pk);
      exit(-2);
    }
    bucketDefArray->nomos_agent_pk = nomos_agent_pk;
    bucketDefArray->bucket_agent_pk = agent_pk;

    /* loop through rules (bucket defs) to see if there are any package only rules */
    hasPrules = 0;
    for (tmpbucketDefArray = bucketDefArray; tmpbucketDefArray->bucket_pk; tmpbucketDefArray++)
      if (tmpbucketDefArray->applies_to == 'p')
      {
        hasPrules = 1;
        break;
      }

    /*** END initializing bucketDefArray  ***/

    /*** Initialize DEB_SOURCE and DEB_BINARY  ***/
    sprintf(sqlbuf, "select mimetype_pk from mimetype where mimetype_name='application/x-debian-package'");
    result = PQexec(pgConn, sqlbuf);
    if (checkPQresult(result, sqlbuf, __FILE__, __LINE__)) return -1;
    if (PQntuples(result) == 0)
    {
      printf("FATAL: (%s.%d) Missing application/x-debian-package mimetype.\n",__FILE__,__LINE__);
      return -1;
    }
    DEB_BINARY = atoi(PQgetvalue(result, 0, 0));
    PQclear(result);

    sprintf(sqlbuf, "select mimetype_pk from mimetype where mimetype_name='application/x-debian-source'");
    result = PQexec(pgConn, sqlbuf);
    if (checkPQresult(result, sqlbuf, __FILE__, __LINE__)) return -1;
    if (PQntuples(result) == 0)
    {
      printf("FATAL: (%s.%d) Missing application/x-debian-source mimetype.\n",__FILE__,__LINE__);
      return -1;
    }
    DEB_SOURCE = atoi(PQgetvalue(result, 0, 0));
    PQclear(result);
    /*** END Initialize DEB_SOURCE and DEB_BINARY  ***/

    /*** Record analysis start in bucket_ars, the bucket audit trail. ***/
    snprintf(sqlbuf, sizeof(sqlbuf), 
                "insert into bucket_ars (agent_fk, upload_fk, ars_success, nomosagent_fk, bucketpool_fk) values(%d,%d,'%s',%d,%d)",
                 agent_pk, uploadtree.upload_fk, "false", nomos_agent_pk, bucketpool_pk);
    result = PQexec(pgConn, sqlbuf);
    if (checkPQcommand(result, sqlbuf, __FILE__ ,__LINE__)) return -1;
    PQclear(result);

    /* retrieve the ars_pk of the newly inserted record */
    sprintf(sqlbuf, "select ars_pk from bucket_ars where agent_fk='%d' and upload_fk='%d' and ars_success='%s' and nomosagent_fk='%d' \
                  and bucketpool_fk='%d' and ars_endtime is null \
            order by ars_starttime desc limit 1",
            agent_pk, uploadtree.upload_fk, "false", nomos_agent_pk, bucketpool_pk);
    result = PQexec(pgConn, sqlbuf);
    if (checkPQresult(result, sqlbuf, __FILE__, __LINE__)) return -1;
    if (PQntuples(result) == 0)
    {
      printf("FATAL: (%s.%d) Missing bucket_ars record.\n%s\n",__FILE__,__LINE__,sqlbuf);
      return -1;
    }
    ars_pk = atol(PQgetvalue(result, 0, 0));
    PQclear(result);
    /*** END bucket_ars insert  ***/

    if (debug) printf("%s sql: %s\n",__FILE__, sqlbuf);
  
    /* process the tree for buckets 
       Do this as a single transaction, therefore this agent must be 
       run as a single thread.  This will prevent the scheduler from
       consuming excess time (this is a fast agent), and allow this
       process to update bucket_ars.
     */
    rv = walkTree(pgConn, bucketDefArray, agent_pk, head_uploadtree_pk, writeDB, 0, 
             hasPrules);
    /* if no errors and top level is a container, process the container */
    if ((!rv) && (IsContainer(uploadtree.ufile_mode)))
    {
      rv = processFile(pgConn, bucketDefArray, &uploadtree, agent_pk, writeDB, hasPrules);
    }

    /* Record analysis end in bucket_ars, the bucket audit trail. */
    if (ars_pk)
    {
      if (rv)
        snprintf(sqlbuf, sizeof(sqlbuf), 
                "update bucket_ars set ars_endtime=now(), ars_success=false where ars_pk='%d'",
                ars_pk);
      else
        snprintf(sqlbuf, sizeof(sqlbuf), 
                "update bucket_ars set ars_endtime=now(), ars_success=true where ars_pk='%d'",
                ars_pk);

      result = PQexec(pgConn, sqlbuf);
      if (checkPQcommand(result, sqlbuf, __FILE__ ,__LINE__)) return -1;
      PQclear(result);
      if (debug) printf("%s sqlbuf: %s\n",__FILE__, sqlbuf);
    }
  }  /* end of main processing loop */

  lrcache_free(&cacheroot);
  DBclose(DB);
  return (0);
}
