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

namespace Siftware\TokenStore;

class TokenStoreFactory
{
    public static function build($storeType)
    {
        switch ($storeType) {
            case 'File':
                return new TokenStoreFile();
                break;
        }


    }
}