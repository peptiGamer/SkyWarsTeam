<?php

namespace Olimpo\SkyWarsTeamOlimpo;

use Olimpo\SkyWarsTeamOlimpo\GameSender;

Class Resetmap
{
    public $main;
    
    public function __construct(GameSender $main){
        $this->main = $main;
    }
    
    public function reload($lev)
    {
            $name = $lev->getFolderName();
            if ($this->main->getOwner()->getServer()->isLevelLoaded($name))
            {
                    $this->main->getOwner()->getServer()->unloadLevel($this->main->getOwner()->getServer()->getLevelByName($name));
            }
            $zip = new \ZipArchive;
            $zip->open($this->main->getOwner()->getDataFolder() . 'arenas/' . $name . '.zip');
            $zip->extractTo($this->main->getOwner()->getServer()->getDataPath() . 'worlds');
            $zip->close();
            unset($zip);
            $this->main->getOwner()->getServer()->loadLevel($name);
            return true;
    }
}

