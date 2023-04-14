<?php

namespace App\DataFixtures;

use App\Entity\Categories;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoriesFixtures extends Fixture
{
    private $counter = 1;

    public function load(ObjectManager $manager): void
    {
        $categories = ['Débutant·e', 'Confirmé·e', 'Expert·e']; 

        foreach ($categories as $cat) {
            $category = new Categories();
            $category->setName($cat);
            $this->addReference('cat-'.$this->counter, $category);
            $this->counter++;
            $manager->persist($category);
        }
        
        $manager->flush();
    }

       
}
