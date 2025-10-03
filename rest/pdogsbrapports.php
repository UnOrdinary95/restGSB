<?php
/** 
 * Classe d'accès aux données. 
 
 * Utilise les services de la classe PDO
 * pour l'application Gsb Rapport 
 * Les attributs sont tous statiques,
 * les 4 premiers pour la connexion
 * $monPdo de type PDO 
 * $monPdoGsbRapports qui contiendra l'unique instance de la classe
 * @package default
 * @author Cheri Bibi
 * @version    1.0
 * @link       http://www.php.net/manual/fr/book.pdo.php
 */

class PdoGsbRapports{   		
      	 /*--------------------Version locale---------------------------------------- */
	private static $serveur;
	private static $bdd;
	private static $user;
	private static $mdp;
	private static $monPdo;
	private static $monPdoGsbRapports = null;

	public static function init()
	{
    	self::$serveur = 'mysql:host=' . getenv('DB_HOST');
    	self::$bdd = 'dbname=' . getenv('DB_NAME_GSB');
    	self::$user = getenv('DB_USERNAME');
        self::$mdp = getenv('DB_PASSWORD2');
    	}

/**
 * Constructeur privé, crée l'instance de PDO qui sera sollicitée
 * pour toutes les méthodes de la classe
 */				
	private function __construct(){
            self::$monPdo = new PDO(self::$serveur.';'.self::$bdd, self::$user, self::$mdp); 
            self::$monPdo->query("SET CHARACTER SET utf8");
	}
        
	public function _destruct(){
            self::$monPdo = null;
	}
/**
 * Fonction statique qui crée l'unique instance de la classe
 
 * Appel : $instancePdoGsbRapports = PdoGsbRapports::getPdo();
 
 * @return l'unique objet de la classe PdoGsbRapports
 */
	public  static function getPdo(){
		if(self::$monPdoGsbRapports == null){
			self::$monPdoGsbRapports = new PdoGsbRapports();
		}
		return self::$monPdoGsbRapports;  
	}
       

/**
 * Retourne les informations du visiteur
 * @param $login 
 * @param $mdp
 * @return le tableau associatif ou NULL
*/

	public function getLeVisiteur($login, $mdp){
		$req = "select id, nom, prenom from visiteur where login = :login and mdp = :mdp";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':login', $login);
                $stm->bindParam(':mdp', $mdp);
                $stm->execute();
        	$laLigne = $stm->fetch();
                if(count($laLigne)>1)
                   return $laLigne;
                else              
                    return NULL;
	}
        public function getLesRapportsUneDate($idVisiteur, $date){
                $req = "select rapport.id as idRapport, medecin.nom as nomMedecin, medecin.prenom as prenomMedecin, ";
                $req .= "rapport.motif as motif, rapport.bilan as bilan ";
                $req .= " from visiteur, rapport, medecin where visiteur.id = :idVisiteur";
                $req.= " and rapport.idVisiteur = visiteur.id ";
                $req .=" and rapport.idMedecin = medecin.id and rapport.date = :date ";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idVisiteur', $idVisiteur);
                $stm->bindParam(':date', $date);
                $stm->execute();
                $lesLignes = $stm->fetchall();
                return $lesLignes;
         }
         public function getLeRapport($idRapport){
                $req = "select * from rapport where id = :idRapport" ; 
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idRapport', $idRapport);
                $stm->execute();
                $laLigne = $stm->fetch();
                return $laLigne;
         }
        public function majRapport($idRapport,$motif,$bilan){
                 $req = "update rapport set bilan = :bilan ,motif = :motif where id = :idRapport";
                  $stm = self::$monPdo->prepare($req);
                  $stm->bindParam(':idRapport', $idRapport);
                  $stm->bindParam(':motif', $motif); 
                  $stm->bindParam(':bilan', $bilan); 
                  return $stm->execute();
                 
        } 
        public function getLesMedecins($nom){
            
                $req = "select  * from medecin where nom like '" . $nom ."%' order by nom, prenom";
                $rs = self::$monPdo->query($req);
                $lesLignes = $rs->fetchAll();
                return $lesLignes;
        }
        
        public function getLeMedecin($idMedecin){
                $req = "select * from medecin where id = :idMedecin";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idMedecin', $idMedecin); 
		$stm->execute();
                $laLigne = $stm->fetch();
                return $laLigne;
                
            
        }
        
        public function majMedecin($id ,$adresse ,$tel ,$specialite){
             $req = "update medecin set tel = :tel ,adresse = :adresse, ";
              $req .= "specialitecomplementaire = :specialite where id = :idMedecin";
                  $stm = self::$monPdo->prepare($req);
                  $stm->bindParam(':idMedecin', $id);
                  $stm->bindParam(':specialite', $specialite);
                  $stm->bindParam(':tel', $tel); 
                  $stm->bindParam(':adresse', $adresse); 
                  return $stm->execute();
        
        }
        public function getLesRapports($idMedecin){
            $req = "select rapport.motif as motif, rapport.date as date, rapport.bilan as bilan, ";
            $req .= " visiteur.nom as nom, visiteur.prenom as prenom from rapport, medecin, ";
            $req .= "visiteur where rapport.idMedecin = medecin.id ";
            $req .= " and rapport.idVisiteur = visiteur.id and medecin.id = :idMedecin order by date";
            $stm = self::$monPdo->prepare($req);
            $stm->bindParam(':idMedecin', $idMedecin); 
            $stm->execute();
            $lesLignes = $stm->fetchall();
            return $lesLignes;
        }
        public function getLesMedicaments($nom){
            
                $req = "select * from medicament where nomCommercial like '" . $nom ."%' order by nomCommercial";
                $rs = self::$monPdo->query($req);
		$lesLignes = $rs->fetchAll();
                return $lesLignes;
            
        }
        public function ajouterRapport($idMedecin ,$idVisiteur ,$bilan ,$motif ,$date ,$medicaments){
                  $req = "insert into rapport(idMedecin ,idVisiteur ,bilan ,date, motif) " ;
                  $req .= " values (:idMedecin ,:idVisiteur ,:bilan , :date,  :motif )";
                  $stm = self::$monPdo->prepare($req);
                  $stm->bindParam(':idMedecin', $idMedecin);
                  $stm->bindParam(':idVisiteur', $idVisiteur);
                  $stm->bindParam(':motif', $motif); 
                  $stm->bindParam(':bilan', $bilan); 
                  $stm->bindParam(':date', $date); 
                  $retour = $stm->execute();;
                  $idRapport =  self::$monPdo->lastInsertId();   // récupère l'id créé
                  if($medicaments !=0){
                          foreach ($medicaments as $idMedicament =>$qte){
                            //  $idMedicament = $medicament['idMedicament'];
                             // $qte = $medicament['qte'];
                              $req = "insert into offrir(idRapport, idMedicament, quantite) ";
                              $req .= "values( :idRapport, :idMedicament, :qte)  ";
                              $stm = self::$monPdo->prepare($req);
                              $stm->bindParam(':idRapport', $idRapport);
                              $stm->bindParam(':idMedicament', $idMedicament);
                              $stm->bindParam(':qte', $qte);
                              $ret = $stm->execute();
                              if($ret!=1)
                                  $retour = 0;
                          }
                   }
                   return $retour;
                   
        }
        
       
}   // fin classe
?>


