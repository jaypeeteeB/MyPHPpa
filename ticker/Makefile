#
# MyPHPpa ticker
# Copyright (C) 2003 Jens Beyer
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#

LIB =	/usr/lib/libmysqlclient.a -lz
LIB =	-lmysqlclient -lcrypt -lz
LIB =	-lmariadb -lcrypt  -lm
SRC =	ticker.c score.c update.c core_sql.c helper.c battle.c cleanup.c logging.c options.c
OBJ =	$(SRC:.c=.o)

CC =	gcc
CFLAGS=	-O3 -g -Wall
CFLAGS=	-O3 -g -Wall -I/usr/include/mariadb -DMARIADB
LDFLAGS= -O3 $(LIB)

.SUFFIXES: .c .o

.PHONY: update

all:	ticker tick_sleep

test:	$(OBJ)
	gcc -O -g $(OBJ) -o ticker.test $(LIB)

ticker:	$(OBJ)
	gcc -O -g $(OBJ) -o ticker $(LIB)

sql:	test_sql.o core_sql.o options.o
	gcc -O -g test_sql.o core_sql.o options.o -o test_sql $(LIB)

tick_sleep: tick_sleep.o
	gcc -O -g tick_sleep.o -o tick_sleep

update:	
	cp ticker ticker.old
	cp ticker.test ticker

clean:	
	rm -f *.o ticker *~ tick_sleep

$(OBJ): ticker.h logging.h
tick_sleep.o:	tick_sleep.c
