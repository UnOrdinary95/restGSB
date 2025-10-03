<?php
/**
* Script Rest.php contenant le code de la classe abstraite Rest
*
* @author Lycée Jean Rostand stssio
* @package default
*/

/**
* @class Rest
* @brief classe de base d'une API REST
*
* Cette classe générique permet de :
*    + décoder les requêtes client, c'est à dire identifier :
*        - la ressource visée,
*        - l'action demandée (GET, PUT, DELETE, POST)
*    + générer la réponse
*/
abstract class Rest {

    /**
     * Le type de contenu demandé  (json, xml, ...)
     */
    protected $content_type;

    /**
     * Tableau contenant les différents éléments de la requête :
     *   la ressource demandée
     *   les paramètres éventuels
     */
    protected $request;

    /**
     * L'action demandée (méthode HTTP) pour la requête (GET, POST, PUT, DELETE)
     */
    protected $method;

    /**
     * Constructeur de la classe.
     *
     * Analyse la requête et extrait les différentes informations : méthode HTTP,
     * type de contenu demandé, ressource demandée, paramètres de la requête, ...
     */
    public function __construct() {
    //    error_log(print_r( "construction REST : "),3,"log.txt");
        /* Récupère la méthode associée à la requête */
        $this->method = $_SERVER['REQUEST_METHOD'];

        /* Détermine le type de contenu demandé */
       $this->content_type = "application/json";
     /* Les deux lignes suivantes enlevées à cause d'un message
     failed to load reponse data (avec POST )*/
     /*   if (isset($_SERVER['CONTENT_TYPE'])) {
            $this->content_type = $_SERVER['CONTENT_TYPE'];
        }*/
//error_log(print_r( "methode : ".$this->method),3,"log.txt");
        /* Récupère les paramètres de la requête et les stocke dans le tableau $request
           Un status d'erreur est retourné si la demande concerne une méthode
           autre que GET, POST, PUT ou DELETE */
        $this->request = array();
        switch ($this->method) {
            case "POST" :
             $this->request = $this->cleanInputs($_REQUEST);
        //      error_log(print_r( $this->request,true),3,"log.txt");
             break;
            case "DELETE" :
                $this->request = $this->cleanInputs($_POST);
                break;
            case "GET" :
                 $this->request = $this->cleanInputs($_GET);
              //   error_log(print_r( $this->request,true),3,"log.txt");
                 break;
            case "PUT" :
                 $this->request = $this->cleanInputs($_GET);
                break;
            default :
                $this->response('Methode non autorisée', 405);   // Method Not Allowed
                break;
        }
        
        /* Extrait la ressource depuis REQUEST_URI */
        $uri = $_SERVER['REQUEST_URI'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        
        // Enlève le nom du script de l'URI pour obtenir la ressource
        $ressource = str_replace($scriptName, '', $uri);
        
        // Enlève les paramètres de requête (après ?)
        if (($pos = strpos($ressource, '?')) !== false) {
            $ressource = substr($ressource, 0, $pos);
        }
        
        // Nettoie les slashes au début et à la fin
        $this->request['ressource'] = trim($ressource, '/');
    }

    /**
    * Prépare et affiche la réponse HTTP
    *
    * @param string  $data      contenu de la réponse
    * @param integer $status    statut de la réponse
    * @param string  $msg       message éventuel associé au status
    */
    public function response($data, $status) {
        header("HTTP/1.1 " . $status . " " . $this->getStatusMessage($status));
        header("Content-Type:" . $this->content_type);
        header('Access-Control-Allow-Origin: *');
        header ( "Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS" );
        echo $data;
        exit;
    }

    /**
     * Analyse, nettoie et restructure les données passées en paramètre.
     *
     * @param   mixed   $data   données à structurer et nettoyer
     * @return  array           tableau des données restructurées et nettoyées
    */
    private function cleanInputs($data) {
        $clean_input = array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->cleanInputs($v);
            }
        }
        else {
            if (get_magic_quotes_gpc()) {
                $data = trim(stripslashes($data));
            }
            $data = strip_tags($data);
            $clean_input = trim($data);
        }
        return $clean_input;
    }

    /**
    *  Définit le message associé au code status HTTP.
    *
    *  Norme RFC 2616
    *  100 ==> 118 : codes d'information
    *  200 ==> 206 : codes de succès
    *  300 ==> 310 : codes de redirection
    *  400 ==> 417 : codes d'erreur du client
    *  500 ==> 505 : codes d'erreur du serveur
    *
    * @param  integer  $status  status de la réponse HTTP
    * @return string            message correspondant au status
    */
    public function getStatusMessage($status) {
        $message = array(100 => 'Continue',
                        101 => 'Switching Protocols',
                        118 => 'Connection timed out',
                        200 => 'OK',
                        201 => 'Created',
                        202 => 'Accepted',
                        203 => 'Non-Authoritative Information',
                        204 => 'No Content',
                        205 => 'Reset Content',
                        206 => 'Partial Content',
                        300 => 'Multiple Choices',
                        301 => 'Moved Permanently',
                        302 => 'Moved Temporarily',
                        303 => 'See Other',
                        304 => 'Not Modified',
                        305 => 'Use Proxy',
                        307 => 'Temporary Redirect',
                        310 => 'Too many Redirects',
                        400 => 'Bad Request',
                        401 => 'Unauthorized',
                        402 => 'Payment Required',
                        403 => 'Forbidden',
                        404 => 'Not Found',
                        405 => 'Method Not Allowed',
                        406 => 'Not Acceptable',
                        407 => 'Proxy Authentication Required',
                        408 => 'Request Timeout',
                        409 => 'Conflict',
                        410 => 'Gone',
                        411 => 'Length Required',
                        412 => 'Precondition Failed',
                        413 => 'Request Entity Too Large',
                        414 => 'Request-URI Too Long',
                        415 => 'Unsupported Media Type',
                        416 => 'Requested Range Not Satisfiable',
                        417 => 'Expectation Failed',
                        500 => 'Internal Server Error',
                        501 => 'Not Implemented',
                        502 => 'Bad Gateway',
                        503 => 'Service Unavailable',
                        504 => 'Gateway Timeout',
                        505 => 'HTTP Version Not Supported'
        );
        return ($message[$status]) ? $message[$status] : $status[500];
    }
}