<?php

namespace App\DataFixtures;

use Faker;
use App\Entity\Comments;
use App\DataFixtures\TricksFixtures;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CommentsFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');
        $ref = [
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
        for ($i = 0; $i <= 40; $i++) {
            $comment = new Comments();
            $comment->setContent($faker->sentence(5));
            $comment->setCreatedAt(new \DateTimeImmutable());
            $user = $this->getReference('user-'. rand(1, 6));
            $comment->setAuthor($user);
            $trick = $this->getReference($ref[rand(0,9)]);
            $comment->setTrick($trick);
            $manager->persist($comment);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            TricksFixtures::class,
            UsersFixtures::class,
        ];
    }
}
