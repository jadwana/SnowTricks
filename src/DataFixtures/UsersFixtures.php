<?php

namespace App\DataFixtures;

use Faker;
use App\Entity\Users;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersFixtures extends Fixture
{
    private $counter = 1;

    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new Users();
        $admin->setEmail('admin@demo.fr');
        $admin->setUsername('Jadwana');
        $admin->setIsVerified(1);
        $admin->setAvatar('jad.webp');
        $admin->setPassword(
            $this->passwordEncoder->hashPassword($admin, 'admin01')
        );
        $admin->setRoles(['ROLE_ADMIN']);

        $manager->persist($admin);

        $faker = Faker\Factory::create('fr_FR');

        for ($usr = 1; $usr <= 6; $usr++) {
            $user = new Users();
            $user->setEmail($faker->email);
            $user->setUsername($faker->username);
            $user->setIsVerified(1);
            $user->setAvatar($usr.'.webp');
            $user->setPassword(
                $this->passwordEncoder->hashPassword($user, '123456')
            );
            $this->addReference('user-'.$this->counter, $user);
            $this->counter++;
            $manager->persist($user);
        }

        $manager->flush();
    }
}
