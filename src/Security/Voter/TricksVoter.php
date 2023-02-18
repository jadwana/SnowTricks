<?php

namespace App\Security\Voter;

use App\Entity\Tricks;
use Exception;
use phpDocumentor\Reflection\PseudoTypes\False_;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class TricksVoter extends Voter
{
    // on définie les constantes qui seront utilisées
    const EDIT = 'TRICK_EDIT';
    const DELETE = 'TRICK_DELETE';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $trick): bool
    {
        if(!in_array($attribute, [self::EDIT, self::DELETE])){
            return false;
        }
        if(!$trick instanceof Tricks){
            return false;
        }
        return true;

        //peut etre remplacé par cette ligne
        // return in_array($attribute, [self::EDIT, self::DELETE]) && $trick instanceof Tricks;
    }

    protected function voteOnAttribute($attribute, $trick, TokenInterface $token): bool
    {
        // on récupère l'utilisateur à partir du token
        $user = $token->getUser();
        //on vérifie que le compte a été validé
        if(!$user->getIsVerified()) return False;

        //on vérife que l'utilisateur est bien une instance de userinterface
        if(!$user instanceof UserInterface) return false;
        
        // on vérifie si l'utilisateur est admin
        if(!$this->security->isGranted('ROLE_ADMIN')) return TRUE;

        
        // si utilisateur pas admin on verifie les permissions
        switch($attribute){
            case self::EDIT:
                // On vérifie si l'utilisateur peut éditer
                return $this->canEdit();
                break;
            case self::DELETE:
                // On vérifie si l'utilisateur peut supprimer
                return $this->canDelete();
                break;
        }
    }

    private function canEdit(){

       
        return $this->security->isGranted('ROLE_USER');
    }
    private function canDelete(){
        return $this->security->isGranted('ROLE_USER');
    }
}