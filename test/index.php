<?php
/*
 * This file is part of the siftware/live-connect package.
 *
 * (c) 2014 Siftware <code@siftware.com>
 *
 * Author - Darren Beale @bealers
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . "/bootstrap.php";


print "<pre>";

print_r(json_decode($liveConnect->getProfile()));
print_r(json_decode($liveConnect->getContacts()));

print "</pre>";