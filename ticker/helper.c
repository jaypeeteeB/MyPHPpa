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

#include "ticker.h"

/**** debug helper routines ********/

void print_fields (MYSQL_RES *result) 
{
  unsigned int num_fields;
  unsigned int i;
  MYSQL_FIELD *field;

  num_fields = mysql_num_fields(result);
  for(i = 0; i < num_fields; i++)
    {
      field = mysql_fetch_field_direct(result, i);
      fprintf(logfile, "%s[%u] ", field->name, i);
    }
  fprintf (logfile, "\n");
}

void print_res (MYSQL_RES *result)
{
  MYSQL_ROW row;
  unsigned int num_fields;

  int i;
  
  num_fields = mysql_num_fields(result);

  while ((row = mysql_fetch_row (result))) {

    unsigned long *lengths;
    lengths = mysql_fetch_lengths(result);
        
    for(i = 0; i < num_fields; i++) {
      fprintf(logfile, "[%.*s] ", (int) lengths[i], row[i] ? row[i] : "NULL");
    }
    fprintf(logfile, "\n");
  }
}

void debug (int level, const char *msg)
{
  if (DEBUG >= level)
    fprintf (logfile, "[%d] %s\n",level, msg);
}
