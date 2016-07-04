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
#include <stdarg.h>

#include "ticker.h"

static FILE *sql_log=NULL;

/********** core routines **********/

void check_error (MYSQL *mysql) 
{
  if(mysql_errno(mysql)) {
    fprintf(logfile, "Error: %s\n", mysql_error(mysql));
    exit (2);
  }
}

MYSQL *init_connection (options *opt) 
{
  char *db_host = "localhost";
  char *db_user = "db_user_mypa";
  char *db_passwd = "pw_db__mypa";
  char *db_db = "db_mypa";
  char *db_socket = "/var/lib/mysql/mysql.sock";
  unsigned int db_port = 3306;
  unsigned int db_client_flag = CLIENT_COMPRESS|CLIENT_IGNORE_SPACE;

  MYSQL *mysql;

  mysql = mysql_init (NULL);
  if (!mysql) {
    fprintf (logfile, "Failed to initialize MYSQL\n");
    exit (1);
  }
  
  if (opt) {
    if (!mysql_real_connect (mysql, 
			     opt->db_host, opt->db_user, opt->db_passwd,
			     opt->db_db, opt->db_port, opt->db_socket, 
			     db_client_flag)) {
      fprintf(logfile, "Error connecting: %s\n", mysql_error(mysql));
      exit (1);
    }

    if (opt->sql_log)
      if (!(sql_log = fopen(opt->sql_log,"a"))) {
	fprintf (stderr, "Could not open sql-log: %s\n", opt->sql_log);
      }

  } else {
    if (!mysql_real_connect (mysql, 
			     db_host, db_user, db_passwd,
			     db_db, db_port, db_socket, 
			     db_client_flag)) {
      fprintf(logfile, "Error connecting: %s\n", mysql_error(mysql));
      exit (1);
    }
  }

  return mysql;
}

MYSQL_RES *do_query (MYSQL* mysql, const char *query) 
{
  MYSQL_RES *res;

  if (sql_log) {
    fprintf (sql_log, "%s\n", query);
  }

  if (mysql_query (mysql, query)) {
    fprintf(logfile, "Error in query: %s\n", mysql_error(mysql));
    return NULL;
  }

  /* all at once */
  res = mysql_store_result(mysql);

  if (!res) {
    /* fprintf(logfile, "No result %s\n", query);
     */
    if ( mysql_field_count(mysql) ) {

      fprintf(logfile, "Error storing: %s\n", mysql_error(mysql));
      return NULL;
    } else {
      unsigned int num_rows;

      num_rows = mysql_affected_rows (mysql);
      /* fprintf(logfile, "Affected rows %d\n", num);
       */
      return ((MYSQL_RES *) num_rows);
    }
  }

  return res;
}

MYSQL_RES * vx_query(MYSQL* mysql, const char *format, ...)
{
   static char buf[1024];
   va_list args;

   va_start(args, format);
   vsnprintf(buf, 1023, format, args);
   va_end(args);

   return do_query (mysql, buf);
}

void close_connection (MYSQL* mysql)
{
  if (sql_log)
    fclose(sql_log);

  mysql_close(mysql);
}

