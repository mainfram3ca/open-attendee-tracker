<?
/*
    Copyright (C) 2013  Mainfram3.ca

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function unique_md5() {
    mt_srand(microtime(true)*100000 + memory_get_usage(true));
    return md5(uniqid(mt_rand(), true));
}

echo crypt($_GET['pass'], "$1$" . unique_md5() . "$");
