<?php

namespace App\DataFixtures;

use App\Entity\Tricks;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class TricksFixtures extends Fixture
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        foreach ($this->getTrickData() as [$name, $description]) {
            $trick = new Tricks();
            $trick->setName($name);
            $trick->setDescription($description);
            $trick->setSlug($this->slugger->slug($trick->getname())->lower());
            $trick->setCreatedAt(new \DateTimeImmutable());
            //On va chercher une référence de catégorie
            $category = $this->getReference('cat-'. rand(1, 3));
            $trick->setCategory($category);
        
        
            $this->addReference($trick->getSlug(), $trick);
            $manager->persist($trick);
        }

        $manager->flush();
    }

    private function getTrickData(): array
    {
        return [
            ['japan',
            'Saisie de l\'avant de la planche, avec la main avant, du côté de la carre frontside.'
            ],
            ['Indy grab',
                'Le terme Indy n\'a pas d\'explication particulière, mais un Grab en anglais veut dire saisir/attraper quelque chose avec sa main. Dans le contexte du snowboard, tu veux essayer de saisir le bord de ta planche avec ta main pendant que tu voles dans les airs. 
                Pour faire un Indy Grab, il faut décoller d\'un kicker et utiliser la technique du Ollie. Le Ollie te permettra de prendre plus de hauteur au départ du saut et de suffisamment plier tes jambes pour ensuite attraper ta planche en l’air. Une fois en l’air, il faut saisir le bord de ta planche entre les deux fixations avec ta main arrière. La durée du Grab dépendra de la hauteur que tu prendras sur le saut. Mais surtout n’oublie pas de relâcher la planche avant l’atterrissage, car sinon tu risques de te faire très mal aux doigts ! Tout est dans le relâchement en douceur de la prise Indy, qui te permettra de lâcher prise à temps pour atterrir comme un patron.'
            ],
            ['Jib',
                'Le Jib est l\'une des figures de base à apprendre quand tu te lances dans le park, car il est utilisé sur la plupart des figures freestyle. Le jibbing consiste à chevaucher, sauter ou glisser sur tout ce qui n\'est pas une surface piquée, comme les rails, les bancs ou une bûche.'
            ],
            ['Butter',
                'Pour réussir cette figure amusante, il s\'agit de soulever le nez ou la queue de la planche dans les airs, tout en restant en contact avec la neige, ce qui te permettra de faire des variations de spin tout en glissant sur la neige. Habituellement, quand tu fais des virages pour changer de direction sur les pistes, tu utiliseras les lames/bords de ton snowboard pour garder le contrôle. Mais pour le Butter, il faut essayer de rester bien centré sur ta planche, car si tu utilises les lames/bords de ton snowboard, tu risques de tomber. Le but est de garder sa planche au plat sur la neige tout en gardant le Nose ou le Tail en l\'air, pendant que tu glisses sur la piste.'
            ],
            ['Tail Grab',
                'Pour ceux qui cherchent à impressionner, essayez le Tail Grab comme prochaine étape. Comme pour le Indy Grab, commence par faire un Ollie pour prendre de la hauteur depuis le saut, et une fois en l’air, attrapes la queue de la planche avec ta main arrière. C\'est aussi simple que cela (ou pas si simple, mais personne ne se doutera de rien).'
            ],
            ['Melon',
                'Passe la main avant derrière ton genou et attrape le bord des talons entre les fixations.'
            ],
            ['Backflip',
                'Le Backflip est l\'une des figures les plus emblématiques du snowboard ! Une fois que tu as quitté le kicker et que tu as assez de hauteur et d\'élan, il faut jeter ton poids en arrière pour faire une rotation verticale - ou un Flip - pour avoir la tête en bas et les jambes en haut, tout en relâcher tes jambes au bon moment pour atterrir. Tu as probablement eu quelques accidents désagréables en le perfectionnant, mais cela en vaut la peine lorsque tu vois les visages et les acclamations de tes spectateurs.'
            ],
            ['Mute',
               'La main avant grabbe la carre frontside entre les pieds'
            ],
            ['Nose Grab',
               'Le Nose Grab est l\'inverse du Tail Grab. Au lieu d\'attraper la queue de la planche, il faut attraper le nez de la planche. Pour ce trick, il faut essayer de plier ta jambe avant après le Ollie, pour faire remonter la planche dans un mouvement plus vertical en l\'air, ce qui te donnera plus de facilité à attraper le Nose.
               À noter que pour le Nose et le Tail Grab, il faut quand même avoir un peu plus de hauteur pour avoir le temps d’attraper la planche et de la relâcher à temps avant l\'atterrissage. Cela veut dire que tu devras prendre plus de vitesse avant le saut et ajouter plus de puissance à ton Ollie.'
            ],
            ['Stalefish',
               'la main arrière grabbe la carre backside entre les talons' 
            ],
        ];
    }
}



