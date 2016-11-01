<?php

/*
 * MyPHPpa
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

require "popup_header.inc";
require "standard.php";
include_once "alliance_func.inc";

require_once "navigation.inc";

echo "<div id=\"main\">\n";
/* top table is written now */
top_header($myrow);

if (!ISSET($msg)) $msg = "";
titlebox("Alliance listing", $msg);

echo "<center>\n";

list_alliances();
?>
</center>
</div>

<?php
require "footer.php";
?>
