<?php

declare(strict_types=1);

use DaveLiddament\TestSelector\Config\DitsConfig;

return DitsConfig::create()
    ->sourceDir('lib/')
    ->commit('custom-sha')
    ->includeUnstaged()
    ->format('json');
