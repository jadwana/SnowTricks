<?php

namespace App\DataFixtures;

use App\Entity\Videos;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class VideosFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
            $video = new Videos();
            $video->setLink('https://youtube.com/embed/CzDjM7h_Fwo');
            $trick = $this->getReference('japan');
            $video->setTricks($trick);
            $manager->persist($video);

            $video = new Videos();
            $video->setLink('https://www.dailymotion.com/embed/video/x3rqao8');
            $trick = $this->getReference('japan');
            $video->setTricks($trick);
            $manager->persist($video);
        

        $manager->flush();
    }
}
