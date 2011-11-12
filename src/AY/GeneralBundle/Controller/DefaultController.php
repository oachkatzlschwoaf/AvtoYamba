<?php

namespace AY\GeneralBundle\Controller;

# Default
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

# Entities
use AY\GeneralBundle\Entity\Message;
use AY\GeneralBundle\Entity\Notify;
use AY\GeneralBundle\Entity\Subscribe;
use AY\GeneralBundle\Entity\Util;

# Forms
use AY\GeneralBundle\Form\MessageType;
use AY\GeneralBundle\Form\SubscribeType;

class DefaultController extends Controller {
    
    public function indexAction(Request $req) {
        # Create message form 
        $message = new Message();
        $form = $this->createForm(new MessageType(), $message);

        # Show stream 
        $rep = $this->getDoctrine()->getRepository('AYGeneralBundle:Message');
        $q = $rep->createQueryBuilder('p')
            ->orderBy('p.created_at', 'DESC')
            ->setMaxResults(10)
            ->getQuery();

        $messages = $q->getResult();

        # Process form
        if ($req->getMethod() == 'POST') {

            $form->bindRequest($req);

            if ($form->isValid()) {
                # Save new message
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($message);
                $em->flush();
                
                # Create notify for message
                $notify = new Notify;
                $notify->setMessageId( $message->getId() );

                $em->persist($notify);
                $em->flush();

                # Flash message
                $this->get('session')->setFlash('notice', 'Сообщение добавлено!');

                # Redirect to number page
                return $this->redirect( $this->generateUrl('number', array( 'number' => $message->getNumber() )) );
            }

        }

        return $this->render(
            'AYGeneralBundle:Default:index.html.twig',
            array(
                'messages' => $messages,
                'form'     => $form->createView(),
            )
        );

    }
    
    public function numberAction(Request $req) {
        # Create subscribe form
        $form_ss = $this->createForm(new SubscribeType());

        # Find messages by number
        $number = $req->get('number');

        $rep = $this->getDoctrine()->getRepository('AYGeneralBundle:Message');
        $q = $rep->createQueryBuilder('p')
            ->where('p.number = :number')
            ->orderBy('p.created_at', 'DESC')
            ->setMaxResults(10)
            ->setParameter('number', $number)
            ->getQuery();

        $messages = $q->getResult();

        # Process form
        if ($req->getMethod() == 'POST') {
            $form_ss->bindRequest($req);

            if ($form_ss->isValid()) {
                # Create new subscribe
                $email = $form_ss->get('email')->getData();
                $phone = $form_ss->get('phone')->getData();

                $subscribe = new Subscribe();
                $subscribe->setNumber($number);
                if ($email) 
                    $subscribe->setEmail($email);
                if ($phone) 
                    $subscribe->setPhone($phone);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($subscribe);
                $em->flush();

                # Flash message
                $this->get('session')->setFlash('notice', 'Подписка сохранена!');
                
                # Redirect
                return $this->redirect( $this->generateUrl('number', array( 'number' => $number )) );
            }
        }            
        
        return $this->render(
            'AYGeneralBundle:Default:number.html.twig',
            array(
                'number'   => $number,
                'messages' => $messages,
                'ss_form'  => $form_ss->createView(),
            )
        );

    }

    public function searchNumberAction(Request $req) {
        # Clear nubmer
        $number = $req->request->get('number'); 
        $number = str_replace(" ", "", $number);

        $util = new Util();
        $number = $util->translateForward($number);

        # Redirect to number page
        return $this->redirect( 
            $this->generateUrl('number', array('number' => $number))  
        );
    }

    public function updateMessageAction(Request $req) {
        $answer = array('update' => 'fail');

        if ($req->getMethod() == 'POST') {
            
            # Find message by id
            $rep = $this->getDoctrine()->getRepository('AYGeneralBundle:Message');
            $message = $rep->find( $req->request->get('id') );

            if (!$message) {
                return new Response( json_encode($answer) );
            }

            # Update message
            $message->setTweetId( $req->request->get('tweet_id') );
            
            if ( $img = $req->request->get('image') ) {
                $message->setImage($img);    
                $message->setImageThumb( $img.':thumb' );
                $message->setImageTmp(null);
            }
              
            $em = $this->getDoctrine()->getEntityManager();
            $em->flush();

            $answer = array('update' => 'done');
        }

        return new Response( json_encode($answer) );
    }

    public function postMessageAction(Request $req) {
        $answer = array('post' => 'fail');

        if ($req->getMethod() == 'POST') {
            # Create new message
            $message = new Message();             
            $message->setNumber( $req->request->get('number') );
            $message->setUserName( $req->request->get('user_name') );
            $message->setText( $req->request->get('text') );
            $message->setTweetId( $req->request->get('tid') );

            if ($req->request->get('image')) {
                $img = $req->request->get('image');
                $message->setImage( $img );
                $message->setImageThumb( $img.':thumb' );
            }    

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($message);
            $em->flush();

            # Create notify for message
            $notify = new Notify;
            $notify->setMessageId( $message->getId() );
            $notify->setTweetDone(1);

            $em->persist($notify);
            $em->flush();

            $answer = array('post' => 'done');
        }

        return new Response( json_encode($answer) );
    }

}
