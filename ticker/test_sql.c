/*
 * MyPHPpa ticker
 * Copyright (C) 2003, 2007 Jens Beyer
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <time.h>
#include <sys/time.h>
#include <signal.h>

#include "ticker.h"

FILE *logfile;

int main(int argc, char *argv[])
{
  MYSQL *mysql;
  MYSQL_RES *res;
  MYSQL_ROW row;
  options *opt = NULL;
  char *search = argv[1];

  if (argc<2) { 
    fprintf( stderr, "Usage: %s [mpa.cfg] search_string\n", argv[0]);
    exit (1);
  }

  logfile = stdout;
  if (argc > 2) {
    opt = read_cfg (argc, argv);
    search = argv[2];
  } 
  mysql = init_connection(opt);

  res = vx_query (mysql,"SELECT id, leader, planetname, x, y, z "\
	          "FROM planet WHERE leader like '%%%s%%' " \
                  "OR planetname like '%%%s%%'",
                  search);

  if (res && mysql_num_rows(res)) {
    while ((row = mysql_fetch_row (res))) {
      fprintf (stdout, "Found: [%s] %s of %s (%s:%s:%s)\n",
               row[0], row[1], row[2], row[3], row[4], row[5]);
    }

    check_error (mysql);
    mysql_free_result(res);
  } else 
    fprintf (stdout, "No such Planet [%s].\n", search);

  return (0);
}
