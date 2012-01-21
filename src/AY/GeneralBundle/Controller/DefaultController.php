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
use AY\GeneralBundle\Entity\SmsGate;
use AY\GeneralBundle\Entity\Config;

# Forms
use AY\GeneralBundle\Form\MessageType;
use AY\GeneralBundle\Form\SubscribeType;
use AY\GeneralBundle\Form\UnsubscribePhoneType;

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

        # Create message form
        $form = $this->createForm(new MessageType());

        # Create unsubsribe phone form
        $form_unss = $this->createForm(new UnsubscribePhoneType());

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
            $answer = array('fail' => 'invalid_form');

            if ($form_ss->isValid()) {
                # Create new subscribe
                $email = $form_ss->get('email')->getData();
                $phone = $form_ss->get('phone')->getData();

                if (!$email && !$phone) {
                    return new Response( json_encode($answer) );
                }

                # Check uniq
                $ss_rep = $this->getDoctrine()->getRepository('AYGeneralBundle:Subscribe');
                $ss_emails = array();
                $ss_phones = array();

                if ($email) {
                    $q = $ss_rep->createQueryBuilder('p')
                        ->where('p.number = :number and p.email = :email')
                        ->setParameter('number', $number)
                        ->setParameter('email', $email)
                        ->getQuery();

                    $ss_emails = $q->getResult();
                }

                if ($phone) {
                    $util = new Util();
                    $clean_phone = $util->cleanPhone($phone);

                    $q = $ss_rep->createQueryBuilder('p')
                        ->where('p.number = :number and p.phone = :phone')
                        ->setParameter('number', $number)
                        ->setParameter('phone', $clean_phone)
                        ->getQuery();

                    $ss_phones = $q->getResult();
                }

                if (count($ss_emails) > 0 && count($ss_phones) > 0) {
                    $answer['fail'] = 'too_much_all';
                    return new Response( json_encode($answer) );
                }

                if (!$phone && count($ss_emails) > 0) {
                    $answer['fail'] = 'too_much_email';
                    return new Response( json_encode($answer) );
                }

                if (!$email && count($ss_phones) > 0) {
                    $answer['fail'] = 'too_much_phone';
                    return new Response( json_encode($answer) );
                }

                # Save subscribe

                if ($email && count($ss_emails) == 0) { 
                    $subscribe = new Subscribe();

                    $subscribe->setNumber($number);
                    $subscribe->setEmail($email);

                    $em = $this->getDoctrine()->getEntityManager();
                    $em->persist($subscribe);

                    $em->flush();
                }

                if ($phone && count($ss_phones) == 0) { 
                    $subscribe = new Subscribe();

                    $subscribe->setNumber($number);
                    $subscribe->setPhone($phone);

                    $em = $this->getDoctrine()->getEntityManager();
                    $em->persist($subscribe);

                    $em->flush();
                }

                # Flash message
                $this->get('session')->setFlash('notice', 'Подписка сохранена!');
                
                # Return answer 
                $answer['fail'] = 0;
                return new Response( json_encode($answer) );

            } else {
                return new Response( json_encode($answer) );

            }
        }            
        
        return $this->render(
            'AYGeneralBundle:Default:number.html.twig',
            array(
                'number'    => $number,
                'messages'  => $messages,
                'unss_form' => $form_unss->createView(),
                'ss_form'   => $form_ss->createView(),
                'form'      => $form->createView(),
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

    public function showCarNumberAction($format = 25, $number = null) {
        $sprite_name = '115x25_sprite.png';

        if ($format == 50) {
            $sprite_name = '225x50_sprite.png';
        }

        $trans = array(
            25 => array(
                'gen' => array(
                    1 => array(     0,     0, 12, 20), 
                    2 => array(   -15,     0, 12, 20),
                    3 => array(   -30,     0, 12, 20),
                    4 => array(   -45,     0, 12, 20),
                    5 => array(   -60,     0, 12, 20),
                    6 => array(   -75,     0, 12, 20),
                    7 => array(   -90,     0, 12, 20),
                    8 => array(  -105,     0, 12, 20),
                    9 => array(  -120,     0, 12, 20),
                    0 => array(  -135,     0, 12, 20),
                    'a' => array(   0,   -35, 11, 15),
                    'b' => array( -15,   -35, 11, 15),
                    'e' => array( -30,   -35, 11, 15),
                    'k' => array( -45,   -35, 11, 15),
                    'm' => array( -60,   -35, 12, 15),
                    'h' => array( -75,   -35, 11, 15),
                    'o' => array( -90,   -35, 11, 15),
                    'p' => array(-105,   -35, 11, 15),
                    'c' => array(-120,   -35, 11, 15),
                    't' => array(-135,   -35, 11, 15),
                    'y' => array(   0,   -50, 11, 15),
                    'x' => array( -15,   -50, 11, 15),
                ),
                'reg' => array(
                    1 => array(    -3,   -20, 8, 15), 
                    2 => array(   -18,   -20, 8, 15),
                    3 => array(   -33,   -20, 8, 15),
                    4 => array(   -48,   -20, 8, 15),
                    5 => array(   -63,   -20, 8, 15),
                    6 => array(   -78,   -20, 8, 15),
                    7 => array(   -93,   -20, 8, 15),
                    8 => array(  -108,   -20, 8, 15),
                    9 => array(  -123,   -20, 8, 15),
                    0 => array(  -138,   -20, 8, 15),
                ),
            ),
            50 => array(
                'gen' => array(
                    1 => array(     0,     0, 23, 40), 
                    2 => array(   -25,     0, 23, 40),
                    3 => array(   -50,     0, 23, 40),
                    4 => array(   -75,     0, 23, 40),
                    5 => array(  -100,     0, 23, 40),
                    6 => array(  -125,     0, 23, 40),
                    7 => array(  -150,     0, 23, 40),
                    8 => array(  -175,     0, 23, 40),
                    9 => array(  -200,     0, 23, 40),
                    0 => array(  -225,     0, 23, 40),
                    'a' => array(   0,   -70, 23, 35),
                    'b' => array( -25,   -70, 23, 35),
                    'e' => array( -50,   -70, 23, 35),
                    'k' => array( -75,   -70, 23, 35),
                    'm' => array(-100,   -70, 23, 35),
                    'h' => array(-125,   -70, 23, 35),
                    'o' => array(-150,   -70, 23, 35),
                    'p' => array(-175,   -70, 23, 35),
                    'c' => array(-200,   -70, 23, 35),
                    't' => array(-225,   -70, 23, 35),
                    'y' => array(   0,  -105, 23, 35),
                    'x' => array( -25,  -105, 23, 35),
                ),
                'reg' => array(
                    1 => array(    -5,   -40, 16, 30), 
                    2 => array(   -30,   -40, 16, 30),
                    3 => array(   -55,   -40, 16, 30),
                    4 => array(   -80,   -40, 16, 30),
                    5 => array(  -105,   -40, 16, 30),
                    6 => array(  -130,   -40, 16, 30),
                    7 => array(  -160,   -40, 16, 30),
                    8 => array(  -180,   -40, 16, 30),
                    9 => array(  -205,   -40, 16, 30),
                    0 => array(  -230,   -40, 16, 30),
                ),
            )
        );

        # Parse number
        $match = array();
        preg_match('/^([a-z]\d{3}[a-z]{2})(\d+)$/', $number, $match);

        $ret_number = array();
        if ($num_arr = str_split($match[1])) {
            foreach ($num_arr as $c) {
                if (isset($trans[$format]['gen'][$c])) {
                    array_push($ret_number, $trans[$format]['gen'][$c]); 
                }
            }
        }

        $ret_region = array();
        if ($num_arr = str_split($match[2])) {
            foreach ($num_arr as $c) {
                if (isset($trans[$format]['reg'][$c])) {
                    array_push($ret_region, $trans[$format]['reg'][$c]); 
                }
            }
        }
        
        return $this->render('AYGeneralBundle:Default:showCarNumber.html.twig',
            array( 'number' => $number, 'format' => $format, 'sprite' => $sprite_name, 'num_off' => $ret_number, 
                   'reg_off' => $ret_region )
        );
    }

    public function unsubscribeEmailAction($number, $code) {
        
        $ss_rep = $this->getDoctrine()->getRepository('AYGeneralBundle:Subscribe');

        $util = new Util();
        $res = $util->decodeEmailCode($code, $ss_rep);

        if ($res) {
            $subscribe = $ss_rep->findOneById($res);
            $em = $this->getDoctrine()->getEntityManager();
            $em->remove($subscribe);
            $em->flush();

            $this->get('session')->setFlash('unsubscribe_message', 'done');
        } else {
            $this->get('session')->setFlash('unsubscribe_message', 'fail');
        }

        return $this->redirect( $this->generateUrl('number', array( 'number' => $number )) );
    }

    public function unsubscribePhoneAction(Request $req) {
        $number = $req->get('number');

        $answer = array(); 
        $answer['fail'] = 1;

        $form_unss = $this->createForm(new UnsubscribePhoneType());

        if ($req->getMethod() == 'POST') {
            $form_unss->bindRequest($req);
            $phone = $form_unss->get('phone')->getData();
            $code  = $form_unss->get('code')->getData();

            # Check phone 
            $util = new Util();
            $clean_phone = $util->cleanPhone($phone);

            $ss_rep = $this->getDoctrine()->getRepository('AYGeneralBundle:Subscribe');

            $q = $ss_rep->createQueryBuilder('p')
                ->where('p.number = :number and p.phone = :phone')
                ->setParameter('number', $number)
                ->setParameter('phone', $clean_phone)
                ->getQuery();

            $ss_phones = $q->getResult();

            if (count($ss_phones) == 0) {
                $answer['fail'] = 'no_phone';
                return new Response( json_encode($answer) );
            }

            $ss = $ss_phones[0];

            if (!$code) {
                # Generate code
                $code = $util->generatePassword(4);
                 
                $ss->setCode($code);
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($ss);
                $em->flush();

                # Send code as sms
                $sms = new SmsGate();
                $sms->setLogin('avtoyamba@gmail.com'); # FIXIT: UNHARDCODE PLEASE
                $sms->setPass('MVTSdFz');
                $sms_answer = $sms->sendSms($clean_phone, $code);

                if (!$sms_answer) {
                    $answer['fail'] = 'broken_sms';

                    return new Response( json_encode($answer) );
                }

                $answer['fail'] = 0;

                return new Response( json_encode($answer) );

            } else {
                
                $true_code = $ss->getCode();

                if ($true_code == $code) {
                    # Remove subscribe
                    $em = $this->getDoctrine()->getEntityManager();
                    $em->remove($ss);
                    $em->flush();

                    $answer['unsubscribe'] = 1;
                    $answer['fail'] = 0;
                    
                } else {
                    $answer['fail'] = 'incorrect_code';

                }

                return new Response( json_encode($answer) );

            }
        }

        return new Response( json_encode($answer) );
    }

    public function moderateMessagesAction(Request $req) {

        $em = $this->getDoctrine()->getEntityManager();

        # Get last moderated id
        $rep = $this->getDoctrine()->getRepository('AYGeneralBundle:Config');
        $q = $rep->createQueryBuilder('p')
            ->where('p.parameter = :parameter')
            ->setParameter('parameter', 'moderated_id')
            ->getQuery();
         
        $config_val     = $q->getResult();
        $last_moderated = 0;

        if (count($config_val) > 0) {
            $last_moderated = $config_val[0]->getValue();
        } else {
            $config = new Config();
            $config->setParameter('moderated_id');
            $config->setValue(0);

            $em->persist($config);
            $em->flush();
        }


        $next = $req->get('next');
        if ($next && $next > $last_moderated) {
            $last_moderated = $next;

            $rep = $this->getDoctrine()->getRepository('AYGeneralBundle:Config');
            $q = $rep->createQueryBuilder('p')
                ->where('p.parameter = :parameter')
                ->setParameter('parameter', 'moderated_id')
                ->getQuery();
             
            $config_val = $q->getSingleResult();
            $config_val->setValue($next);

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($config_val);
            $em->flush();
        }


        # Get messages to moderate
        $query = $em->createQuery(
            'select p from AYGeneralBundle:Message p where p.id > :last_moderated order by p.id desc'
        )->setParameter('last_moderated', $last_moderated)
         ->setMaxResults(10);

        $messages = $query->getResult();
        

        return $this->render(
            'AYGeneralBundle:Default:admin_moderate.html.twig',
            array(
                'last_moderated_id' => $last_moderated,    
                'messages'          => $messages,
            )
        );

    }

    public function deleteMessageAction(Request $req) {
        $answer = array();

        if ($req->getMethod() == 'POST') {
            $id = $req->get('id');

            $message = $this->getDoctrine()
                ->getRepository('AYGeneralBundle:Message')
                ->find($id);

            if (isset($message)) {
                $em = $this->getDoctrine()->getEntityManager();
                $em->remove($message);
                $em->flush();

                $answer['done'] = 1;
            } else {
                $answer['fail'] = 1;

            }
        }

        return new Response( json_encode($answer) );
    }

}
