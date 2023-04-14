<?php

namespace App\DataFixtures;

use App\Entity\Medias;
use App\DataFixtures\TricksFixtures;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class MediasFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $paths = [
            'backflip',
            'indy-grab',
            'japan',
            'butter',
            'jib',
            'melon',
            'mute',
            'nose-grab',
            'stalefish',
            'tail-grab'
        ];
        foreach ($paths as $path){
            $media = new Medias();
            $media->setPath($path.'.webp');
            $media->setMain(0);
            $trick = $this->getReference($path);
            $media->setTricks($trick);
            $manager->persist($media);
        }
        

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            TricksFixtures::class,
        ];
    }
}
