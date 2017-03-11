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

#ifndef __ticker_h__
#define __ticker_h__

#ifdef MARIADB
#include <mariadb/mysql.h>
#else
#include <mysql/mysql.h>
#endif

#define DEBUG 5

typedef struct options_strct {
  char * db_host;
  char * db_db;
  char * db_user;
  char * db_passwd;
  char * db_socket;
  char * logfile;
  char * sql_log;
  char * tickstart;
  char * tickend;
  int db_port;
  int resource;
} options;

extern unsigned int mytick;
extern FILE* logfile;

/* ticker.c *** print runtime ****/
void print_runtime (struct timeval *start);
void sendmessage (MYSQL *mysql, int pid, const char *msg);

/* helper.c *** debug helper routines ****/

void print_fields (MYSQL_RES *result);
void print_res (MYSQL_RES *result);
void debug (int level, const char *msg);


/* core_sql.c *** core routines **********/

void check_error (MYSQL *mysql);
MYSQL *init_connection ();
MYSQL_RES *do_query (MYSQL* mysql, const char *query);
MYSQL_RES *vx_query (MYSQL* mysql, const char *format, ...);
void close_connection (MYSQL* mysql);

/* battle.c *** battle calc **************/
void calc_battles (MYSQL *mysql, int game_mode);

/* update.c *** updating routines ********/
void update_build (MYSQL *mysql);
void move_fleets (MYSQL *mysql);
void update_research (MYSQL *mysql);
void update_resources (MYSQL *mysql, int havoc);

/* score.c *** recalc correct scores ****/
void calc_score (MYSQL *mysql);

/* cleanup.c *** remove/perform cyclic actions ****/
void do_clean_ups (MYSQL *mysql);

/* options.c  *** parse db config ****/
options *read_cfg (int argc, char *argv[]);

#endif /* __ticker_h__ */
