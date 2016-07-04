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

#ifndef _LOGGING_H_
#define _LOGGING_H_

#define C_LOGIN  1
#define C_LOGOUT 2
#define C_ATTACK 3
#define C_FORUM  4
#define C_INIT   5
#define C_SIGNON 6
#define C_FLOW   7

#define T_IP       1
#define T_BROWSER  2
#define T_AUTO     10

#define T_PROTECTION 1
#define T_DELETED    2
#define T_SLEEP      3

#define T_BATTLE_D   10
#define T_BATTLE_H   11
#define T_BATTLE_A   12

extern void do_log_id (MYSQL *mysql, int id, int class, int type, char *data);

#endif /* _LOGGING_H_ */
