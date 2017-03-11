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
#include <assert.h>
#include <string.h>

#include "ticker.h"

options *read_cfg (int argc, char *argv[]) 
{
  FILE *cfg;
  char line[82];
  char cmd[82], opt[82];
  options *ret;

  if (argc < 2) {
    fprintf (logfile, "Missing argument for DB-parameters\n");
    fprintf (stderr, "Usage: %s padb.cfg\n",argv[0]);
    exit (1);
  }

  if (!(cfg = fopen(argv[1],"r"))) {
    fprintf (logfile, "Could not open %s for reading\n", argv[1]);
    fprintf (stderr, "Usage: %s %s\n", argv[0], argv[1]);
    exit (1);
  }

  ret = calloc (sizeof(options), 1);
  assert (ret);

  while ( fgets (line, 80, cfg) ) {
    char * eq;

    if (! (eq = strchr (line, '=') ) )
      continue;
    *eq = ' ';

    if ( 2 != sscanf (line, "%s %s", cmd, opt) )
      continue;

    /*
     * fprintf (stderr, "Option: [%s]=[%s]\n", cmd, opt);
     */

    if (!strncmp (cmd, "db_host", 7)) {
      ret->db_host = strdup (opt);
    } else if (!strncmp (cmd, "db_db", 5)) {
      ret->db_db = strdup (opt);
    } else if (!strncmp (cmd, "db_user", 7)) {
      ret->db_user = strdup (opt);
    } else if (!strncmp (cmd, "db_passwd", 9)) {
      ret->db_passwd = strdup (opt);
    } else if (!strncmp (cmd, "db_socket", 9)) {
      ret->db_socket = strdup (opt);
    } else if (!strncmp (cmd, "db_port", 7)) {
      ret->db_port = atoi(opt);
    } else if (!strncmp (cmd, "logfile", 7)) {
      ret->logfile = strdup (opt);
    } else if (!strncmp (cmd, "tickstart", 9)) {
      ret->tickstart = strdup (opt);
    } else if (!strncmp (cmd, "tickend", 7)) {
      ret->tickend = strdup (opt);
    } else if (!strncmp (cmd, "sql_log", 7)) {
      ret->sql_log = strdup (opt);
    } else if (!strncmp (cmd, "resource", 7)) {
      if (!strncmp (opt, "havoc", 5))
	ret->resource = 1;
      else
	ret->resource = 0;
    }

  } 
  fclose (cfg);
  return ret;
}

