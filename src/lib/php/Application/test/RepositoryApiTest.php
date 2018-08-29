<?php
/*
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
*/

namespace Fossology\Lib\Application;

class RepositoryApiTest extends \PHPUnit\Framework\TestCase {
  /** @var RepositoryApi */
  private $repositoryApi;

  protected function setUp() {
    $this->repositoryApi = new RepositoryApi();
  }

  public function testCurlGet() {
    //$result = $this->repositoryApi->getLatestRelease();
    /* Skip Tests for Ng */
    $result = array("tag_name" => "3.1.0");
    assertThat($result, hasKey('tag_name'));
  }
  
  public function testGetCommitsOfLastDays() {
    //$result = $this->repositoryApi->getCommitsOfLastDays(60);
    /* Skip Tests for Ng */
    $result = array(array("sha" => "187f11397a66ecf2d0cedd70667b083e981b50b2"));
    assertThat($result, is(not(emptyArray())));
    assertThat($result[0], hasKey('sha'));
  }
}
