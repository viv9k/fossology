/*
 * Copyright (C) 2017-2018, Siemens AG
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

#ifndef OJOS_AGENT_UTILS_HPP
#define OJOS_AGENT_UTILS_HPP

#define AGENT_NAME "ojo"
#define AGENT_DESC "ojo agent"
#define AGENT_ARS  "ojo_ars"

#include "state.hpp"

extern "C" {
#include "libfossology.h"
}

using namespace std;

State getState(fo::DbManager& dbManager);
int queryAgentId(fo::DbManager& dbManager);
int writeARS(const State& state, int arsId, int uploadId, int success, fo::DbManager& dbManager);
void bail(int exitval);
bool processUploadId(const State& state, int uploadId, OjosDatabaseHandler& databaseHandler);

#endif // OJOS_AGENT_UTILS_HPP
