<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Commentaire;
use App\Entity\User;
use App\Entity\Contact;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EvenementRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\EvenementType;
use App\Form\RechercheType;
use App\Form\CommentaireType;
use App\Form\ContactType;
use App\Notification\ContactNotification;

class SiteController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function home(EvenementRepository $repo, Request $request): Response
    {
        $form = $this->createForm(RechercheType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())    // si on fait une recherche
        {
            $data = $form->get('recherche')->getData(); // je récupère la saisie de l'utilisateur
            $evenements = $repo->getEvenementsByName($data);
        }
        else    // pas de recherche : on récupère tous les articles
        {
            $evenements = $repo->findBy([], ["titre" => "ASC"]);
        }

        return $this->render('site/index.html.twig',[
            'evenements' => $evenements,
            'formRecherche' => $form->createView()
        ]);
        // pour envoyer des variables à une vue, on les passe dans un tableau associatif
        // indice => valeur
    }

    /**
     * @Route("/new", name="new_evenement")
     * @Route("/edit/{id}", name="edit_evenement")
     */
    public function form(Request $request, EntityManagerInterface $manager, Evenement $evenement=null)
    {
        if(!$evenement){
            $user = $this->getUser();
            $evenement = new Evenement;
            $evenement->setUserId($user);
        }
    
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
        dump($form);
    
        if($form->isSubmitted() && $form->isValid())
        {
            $manager->persist($evenement);
            $manager->flush();
            $this->addFlash('success','L evenement a bien été posté');
            return $this->redirectToRoute('home',[
                // 'id' => $evenement->getId()
            ]);
        }
        return $this->render('site/form.html.twig',[
            'editMode' => $evenement->getId() !== null,
            'formEvenement' => $form->createView()
        ]);
    }

     /**
     * @Route("/show/{id}", name="show_evenement")
     */
    public function show(Request $request, EntityManagerInterface $manager,Evenement $evenement = null,Commentaire $commentaire=null)
    {
        $user = $this->getUser();
        $commentaire = new Commentaire;
        $commentaire->setUser($user);
        $commentaire->setEvenement($evenement);
        $commentaire->setCreatedAt(new \DateTime());

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);
        dump($form);


        if($form->isSubmitted() && $form->isValid())
        {
            $manager->persist($commentaire);
            $manager->flush();
            return $this->redirectToRoute('show_evenement',[
                'id' => $evenement->getId()
            ]
            );
        }
        if(null === $evenement) {
            $this->addFlash("error", "Evenement Not Found");
            return $this->redirectToRoute("home");
        }else{
        return $this->render('site/show.html.twig',[
            'evenement'=> $evenement,
            'formCommentaire' => $form->createView()
        ]);
    }
    }

    /**
     * @Route("/profil/{id}", name="user_profil")
     */
    public function showUserProfil(User $user = null)
    {

        if(null === $user) {
            $this->addFlash("error", "User Not Found");
            return $this->redirectToRoute("home");
        }else{
            return $this->render('site/userprofil.html.twig',[
                'user'=> $user
            ]);
        }
    }


    /**
     * @Route("/profil", name="show_profil")
     */
    public function showProfil(EvenementRepository $repo)
    {
        $evenements = $repo->findAll();
        return $this->render('site/profil.html.twig',[
            'evenements' => $evenements,
        ]);
    }

            /**
     * @Route("/contact", name="make_contact")
     */
    public function contact(Request $request, EntityManagerInterface $manager, ContactNotification $cn)
    {
        $contact = new Contact;
        $contact->setCreatedAt(new \DateTime());

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $manager->persist($contact);
            $manager->flush();
            $cn->notify($contact);
            $this->addFlash('success', 'Votre message a bien été envoyé !');
            // addFlash() permet de créer des msg de notifications
            // elle prend en param le type et le msg
            return $this->redirectToRoute('make_contact');
            // permet de recharger la page et vider les champs du form
        }
        return $this->render("site/contact.html.twig", [
            'formContact' => $form->createView(),
            'contact' => $contact
        ]);
    }

}
