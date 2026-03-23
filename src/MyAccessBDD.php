<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){  
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
            case "suivi" : 
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "commandesdoc" :
                // $champs contient iddocument
                return $this->selectCommandesDocument($champs);
            case "commandesrevue":
                return $this->selectCommandesRevue($champs);
            case "revuesFinAbo":
                return $this->selectRevuesFinAbonnement();
            case "utilisateur":
                return $this->selectUtilisateur($champs);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }	
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs): ?int {
        switch ($table) {
            case "livre":
                return $this->insertLivre($champs);
            case "dvd":
                return $this->insertDvd($champs);
            case "revue":
                return $this->insertRevue($champs);
            case "commandedocument":
                return $this->insertCommandeDocument($champs);
            case "commanderevue":
                return $this->insertCommandeRevue($champs);
            default:
                return $this->insertOneTupleOneTable($table,$champs);
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs): ?int {
        switch ($table) {
            case "livre":
                return $this->updateLivre($id, $champs);
            case "dvd":
                return $this->updateDvd($id, $champs);
            case "revue":
                return $this->updateRevue($id, $champs);
            case "commandedocument":
                return $this->updateOneTupleOneTable("commandedocument", $id, $champs);
            case "exemplaire":
                return $this->updateExemplaire($champs);
            default:
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }
    }

    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */	
    protected function traitementDelete(string $table, ?array $champs): ?int {
        switch ($table) {
            case "livre":
                return $this->deleteLivre($champs);
            case "dvd":
                return $this->deleteDvd($champs);
            case "revue":
                return $this->deleteRevue($champs);
            case "commandedocument":
                return $this->deleteCommandeDocument($champs);
            case "commanderevue":
                return $this->deleteCommandeRevue($champs);
            default:
                return $this->deleteTuplesOneTable($table, $champs);
        }
    }

    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);  
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);	          
            return $this->conn->queryBDD($requete, $champs);
        }
    }	

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);				
        $champs["id"] = $id;
        $requete .= " where id=:id;";		
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);   
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * Récupère la liste des commandes associées à un document (livre ou DVD).
     * 
     * La liste est triée par date de commande dans l'ordre inverse de la chronologie
     * afin d'afficher les commandes les plus récentes en premier.
     * Chaque commande contient :
     *  - la date de commande
     *  - le montant
     *  - le nombre d'exemplaires commandés
     *  - l'étape de suivi (libellé)
     * 
     * @param array|null $champs Tableau associatif contenant l'identifiant du document (iddocument)
     * @return array|null Liste des commandes du document ou null si paramètre invalide
     */
    private function selectCommandesDocument(?array $champs): ?array {
      if (empty($champs) || !isset($champs["iddocument"]))
                return null;

      $req = "SELECT cd.id, cd.iddocument, c.dateCommande as datecommande, c.montant, cd.nbExemplaire as nbexemplaire,
                     s.libelle as suivi, cd.idsuivi
            FROM commandedocument cd
            JOIN commande c ON c.id = cd.id
            JOIN suivi s ON s.id = cd.idsuivi
            WHERE cd.iddocument = :iddocument
            ORDER BY c.dateCommande DESC;";
          return $this->conn->queryBDD($req, $champs);
    }
    
    /**
     * Récupère la liste des commandes d'une revue.
     * 
     * Une commande de revue correspond à un abonnement ou à un renouvellement
     * d'abonnement. La distinction n'est pas nécessaire côté application.
     * 
     * Les commandes sont triées par date de commande dans l'ordre inverse
     * afin d'afficher les abonnements les plus récents en premier.
     * 
     * @param array|null $champs Tableau associatif contenant l'identifiant de la revue (iddocument)
     * @return array|null Liste des commandes de la revue ou null si paramètre invalide
     */
    private function selectCommandesRevue(?array $champs): ?array {
      if (empty($champs) || !isset($champs["iddocument"]))
                return null;

      $req = "SELECT cr.id, cr.iddocument, cr.datecommande, cr.montant, cr.datefinabonnement
            FROM commanderevue cr
            WHERE cr.iddocument = :iddocument
            ORDER BY cr.datecommande DESC;";
        return $this->conn->queryBDD($req, $champs);
    }
    
    /**
     * Récupère la liste des revues dont l'abonnement se termine dans moins de 30 jours.
     * 
     * Pour chaque revue, seule la date de fin d'abonnement la plus récente est prise
     * en compte (dernier abonnement en cours).
     * 
     * Cette méthode est utilisée au démarrage de l'application afin d'afficher
     * une fenêtre d'alerte informant l'utilisateur des abonnements arrivant à échéance.
     * 
     * Le résultat est trié par date de fin d'abonnement dans l'ordre chronologique.
     * 
     * @return array|null Liste des revues avec leur titre et leur date de fin d'abonnement
     */
    private function selectRevuesFinAbonnement(): ?array {
      $req = "SELECT d.titre, MAX(cr.datefinabonnement) as datefin
            FROM commanderevue cr
            JOIN document d ON d.id = cr.iddocument
            GROUP BY cr.iddocument, d.titre
            HAVING datefin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY datefin ASC;";
        return $this->conn->queryBDD($req);
    }

    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";		
        return $this->conn->queryBDD($requete);	    
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";		
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";	
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs 
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }		    
    /**
     * Compte le nombre d'exemplaires rattachés à un document.
     * Cette méthode est utilisée avant une suppression afin de vérifier
     * qu'aucun exemplaire physique n'est associé au document.
     * Si le nombre est supérieur à 0, la suppression est interdite.
     *
     * @param string $idDoc Identifiant du document
     * @return int Nombre d'exemplaires liés au document
     */
    private function countExemplaires(string $idDoc): int {
        $res = $this->conn->queryBDD("SELECT COUNT(*) AS nb FROM exemplaire WHERE id = :id", ["id" => $idDoc]);
        return ($res && isset($res[0]["nb"])) ? (int) $res[0]["nb"] : 0;
    }
    /**
     * Compte le nombre de commandes associées à un livre ou un DVD.
     * Cette vérification permet d'empêcher la suppression d'un document
     * qui a déjà fait l'objet d'une commande.
     *
     * @param string $idDoc Identifiant du livre ou du DVD
     * @return int Nombre de commandes liées au document
     */
    private function countCommandesLivreDvd(string $idDoc): int {
        $res = $this->conn->queryBDD("SELECT COUNT(*) AS nb FROM commandedocument WHERE idLivreDvd = :id", ["id" => $idDoc]);
        return ($res && isset($res[0]["nb"])) ? (int) $res[0]["nb"] : 0;
    }
    /**
     * Compte le nombre d'abonnements liés à une revue.
     * Une revue ne peut pas être supprimée si elle est associée
     * à au moins un abonnement.
     * @param string $idDoc Identifiant de la revue
     * @return int Nombre d'abonnements liés à la revue
     */
    private function countAbonnementsRevue(string $idDoc): int {
        $res = $this->conn->queryBDD("SELECT COUNT(*) AS nb FROM abonnement WHERE idRevue = :id", ["id" => $idDoc]);
        return ($res && isset($res[0]["nb"])) ? (int) $res[0]["nb"] : 0;
    }
    /**
     * Extrait les champs communs à tous les documents
     * (table document) à partir des données reçues.
     * Cette méthode permet de séparer les champs communs
     * des champs spécifiques (livre, dvd, revue).
     *
     * @param array $champs Tableau contenant l'ensemble des données du document
     * @return array Tableau contenant uniquement les champs de la table document
     */
    private function splitChampsDocument(array $champs): array {
        // champs communs table document
        $docKeys = ["id", "titre", "image", "idRayon", "idPublic", "idGenre"];
        $doc = [];
        foreach ($docKeys as $k) {
            if (isset($champs[$k]))
                $doc[$k] = $champs[$k];
        }
        return $doc;
    }
    /**
     * Insère un nouveau livre dans la base de données.
     * L'opération est réalisée dans une transaction afin de garantir
     * la cohérence des données (principe ACID "tout ou rien").
     * Les tables concernées sont :
     *  - document
     *  - livre
     *
     * En cas d'échec sur l'une des insertions, aucune donnée n'est enregistrée.
     *
     * @param array|null $champs Données du livre à insérer
     * @return int|null 1 si succès, null en cas d'échec
     */
    private function insertLivre(?array $champs): ?int {
        if (empty($champs))
            return null;

        $doc = $this->splitChampsDocument($champs);
        $livre = [
            "id" => $champs["id"] ?? null,
            "ISBN" => $champs["ISBN"] ?? null,
            "auteur" => $champs["auteur"] ?? null,
            "collection" => $champs["collection"] ?? null
        ];
        if (empty($doc["id"]))
            return null;

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = $this->insertOneTupleOneTable("document", $doc);
        $ok2 = $this->insertOneTupleOneTable("livre", $livre);

        if ($ok1 === 1 && $ok2 === 1) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }
    /**
     * Insère un nouveau DVD dans la base de données.
     * Les insertions sont effectuées dans une transaction
     * afin d'assurer l'intégrité des données.
     * Tables concernées :
     *  - document
     *  - dvd
     *
     * @param array|null $champs Données du DVD à insérer
     * @return int|null 1 si succès, null en cas d'échec
     */
    private function insertDvd(?array $champs): ?int {
        if (empty($champs))
            return null;

        $doc = $this->splitChampsDocument($champs);
        $dvd = [
            "id" => $champs["id"] ?? null,
            "synopsis" => $champs["synopsis"] ?? null,
            "realisateur" => $champs["realisateur"] ?? null,
            "duree" => $champs["duree"] ?? null
        ];
        if (empty($doc["id"]))
            return null;

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = $this->insertOneTupleOneTable("document", $doc);
        $ok2 = $this->insertOneTupleOneTable("dvd", $dvd);

        if ($ok1 === 1 && $ok2 === 1) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }
    /**
     * Insère une nouvelle revue dans la base de données.
     * L'insertion est transactionnelle pour garantir
     * que les données soient ajoutées de manière cohérente.
     * Tables concernées :
     *  - document
     *  - revue
     *
     * @param array|null $champs Données de la revue à insérer
     * @return int|null 1 si succès, null en cas d'échec
     */
    private function insertRevue(?array $champs): ?int {
        if (empty($champs))
            return null;

        $doc = $this->splitChampsDocument($champs);
        $revue = [
            "id" => $champs["id"] ?? null,
            "periodicite" => $champs["periodicite"] ?? null,
            "delaiMiseADispo" => $champs["delaiMiseADispo"] ?? null
        ];
        if (empty($doc["id"]))
            return null;

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = $this->insertOneTupleOneTable("document", $doc);
        $ok2 = $this->insertOneTupleOneTable("revue", $revue);

        if ($ok1 === 1 && $ok2 === 1) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }
    /**
     * Met à jour les informations d'un livre existant.
     * L'identifiant du document ne peut en aucun cas être modifié.
     * Les mises à jour sont réalisées dans une transaction afin
     * d'éviter toute incohérence entre les tables.
     *
     * @param string|null $id Identifiant du livre
     * @param array|null $champs Champs à mettre à jour
     * @return int|null 1 si succès, null en cas d'échec
     */
    private function updateLivre(?string $id, ?array $champs): ?int {
        if (is_null($id) || empty($champs))
            return null;
        if (array_key_exists("id", $champs))
            return null; // interdit de modifier l'id

        $docUpdate = [];
        foreach (["titre", "image", "idRayon", "idPublic", "idGenre"] as $k) {
            if (isset($champs[$k]))
                $docUpdate[$k] = $champs[$k];
        }

        $livreUpdate = [];
        foreach (["ISBN", "auteur", "collection"] as $k) {
            if (isset($champs[$k]))
                $livreUpdate[$k] = $champs[$k];
        }

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = empty($docUpdate) ? 1 : $this->updateOneTupleOneTable("document", $id, $docUpdate);
        $ok2 = empty($livreUpdate) ? 1 : $this->updateOneTupleOneTable("livre", $id, $livreUpdate);

        if ($ok1 !== null && $ok2 !== null) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }
    /**
     * Met à jour les informations d'un DVD existant.
     * Toute tentative de modification de l'identifiant est refusée.
     * Les mises à jour sont effectuées de manière transactionnelle.
     *
     * @param string|null $id Identifiant du DVD
     * @param array|null $champs Champs à mettre à jour
     * @return int|null 1 si succès, null en cas d'échec
     */
    private function updateDvd(?string $id, ?array $champs): ?int {
        if (is_null($id) || empty($champs))
            return null;
        if (array_key_exists("id", $champs))
            return null;

        $docUpdate = [];
        foreach (["titre", "image", "idRayon", "idPublic", "idGenre"] as $k) {
            if (isset($champs[$k]))
                $docUpdate[$k] = $champs[$k];
        }

        $dvdUpdate = [];
        foreach (["synopsis", "realisateur", "duree"] as $k) {
            if (isset($champs[$k]))
                $dvdUpdate[$k] = $champs[$k];
        }

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = empty($docUpdate) ? 1 : $this->updateOneTupleOneTable("document", $id, $docUpdate);
        $ok2 = empty($dvdUpdate) ? 1 : $this->updateOneTupleOneTable("dvd", $id, $dvdUpdate);

        if ($ok1 !== null && $ok2 !== null) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }
    /**
     * Met à jour les informations d'une revue existante.
     * L'identifiant est strictement immuable.
     * L'opération est protégée par une transaction pour
     * garantir la cohérence des données.
     *
     * @param string|null $id Identifiant de la revue
     * @param array|null $champs Champs à mettre à jour
     * @return int|null 1 si succès, null en cas d'échec
     */
    private function updateRevue(?string $id, ?array $champs): ?int {
        if (is_null($id) || empty($champs))
            return null;
        if (array_key_exists("id", $champs))
            return null;

        $docUpdate = [];
        foreach (["titre", "image", "idRayon", "idPublic", "idGenre"] as $k) {
            if (isset($champs[$k]))
                $docUpdate[$k] = $champs[$k];
        }

        $revueUpdate = [];
        foreach (["periodicite", "delaiMiseADispo"] as $k) {
            if (isset($champs[$k]))
                $revueUpdate[$k] = $champs[$k];
        }

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = empty($docUpdate) ? 1 : $this->updateOneTupleOneTable("document", $id, $docUpdate);
        $ok2 = empty($revueUpdate) ? 1 : $this->updateOneTupleOneTable("revue", $id, $revueUpdate);

        if ($ok1 !== null && $ok2 !== null) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }
    /**
     * Supprime un livre de la base de données.
     * La suppression est refusée si :
     *  - des exemplaires sont associés au livre
     *  - des commandes existent pour ce livre
     *
     * L'opération est réalisée dans une transaction afin de garantir
     * une suppression cohérente entre les tables.
     *
     * @param array|null $champs Contient l'identifiant du livre à supprimer
     * @return int|null 1 si succès, null si suppression interdite ou échec
     */
    /**
     * Met à jour l'état d'un exemplaire (clé composite numero + id).
     * @param array|null $champs Doit contenir "numero", "id" et "idetat"
     * @return int|null nombre de tuples modifiés ou null si paramètres invalides
     */
    private function updateExemplaire(?array $champs): ?int {
        if (empty($champs)) return null;
        $numero = $champs["numero"] ?? null;
        $idDoc  = $champs["id"]     ?? null;
        $idEtat = $champs["idetat"] ?? null;
        if (is_null($numero) || empty($idDoc) || empty($idEtat)) return null;
        $requete = "UPDATE exemplaire SET idetat=:idetat WHERE numero=:numero AND id=:id;";
        return $this->conn->updateBDD($requete, [
            "idetat" => $idEtat,
            "numero" => (int)$numero,
            "id"     => $idDoc
        ]);
    }

    private function deleteLivre(?array $champs): ?int {
        $id = $champs["id"] ?? null;
        if (empty($id))
            return null;

        // règles métier
        if ($this->countExemplaires($id) > 0)
            return null;
        if ($this->countCommandesLivreDvd($id) > 0)
            return null;

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = $this->deleteTuplesOneTable("livre", ["id" => $id]);
        $ok2 = $this->deleteTuplesOneTable("document", ["id" => $id]);

        if ($ok1 !== null && $ok2 !== null) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }
    /**
     * Supprime un DVD de la base de données.
     * La suppression est autorisée uniquement si aucun exemplaire
     * ni aucune commande ne sont liés au DVD.
     * L'opération est transactionnelle.
     *
     * @param array|null $champs Contient l'identifiant du DVD à supprimer
     * @return int|null 1 si succès, null si suppression interdite ou échec
     */
    private function deleteDvd(?array $champs): ?int {
        $id = $champs["id"] ?? null;
        if (empty($id))
            return null;

        if ($this->countExemplaires($id) > 0)
            return null;
        if ($this->countCommandesLivreDvd($id) > 0)
            return null;

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = $this->deleteTuplesOneTable("dvd", ["id" => $id]);
        $ok2 = $this->deleteTuplesOneTable("document", ["id" => $id]);

        if ($ok1 !== null && $ok2 !== null) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }
    /**
     * Supprime une revue de la base de données.
     * La suppression est refusée si la revue est associée
     * à au moins un abonnement ou un exemplaire.
     * L'opération est sécurisée par une transaction.
     *
     * @param array|null $champs Contient l'identifiant de la revue à supprimer
     * @return int|null 1 si succès, null si suppression interdite ou échec
     */
    /**
     * Insère une nouvelle commande de document (livre ou DVD).
     * Insère d'abord dans la table commande (parent),
     * puis dans commandedocument (enfant), dans une transaction.
     *
     * @param array|null $champs Données de la commande
     * @return int|null 1 si succès, null en cas d'échec
     */
    private function insertCommandeDocument(?array $champs): ?int {
        if (empty($champs))
            return null;

        // Générer un id de 5 caractères si absent ou vide
        if (empty($champs["id"])) {
            $champs["id"] = str_pad((string)rand(1, 99999), 5, "0", STR_PAD_LEFT);
        }

        $commande = [
            "id"          => $champs["id"],
            "dateCommande" => $champs["datecommande"] ?? null,
            "montant"     => $champs["montant"] ?? null
        ];

        $cmdDoc = [
            "id"           => $champs["id"],
            "nbExemplaire" => $champs["nbexemplaire"] ?? null,
            "idLivreDvd"   => $champs["iddocument"] ?? null,
            "iddocument"   => $champs["iddocument"] ?? null,
            "idsuivi"      => $champs["idsuivi"] ?? null,
            "datecommande" => $champs["datecommande"] ?? null,
            "montant"      => $champs["montant"] ?? null
        ];

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = $this->insertOneTupleOneTable("commande", $commande);
        $ok2 = $this->insertOneTupleOneTable("commandedocument", $cmdDoc);

        if ($ok1 === 1 && $ok2 === 1) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }

    /**
     * Insère un nouvel abonnement (commande de revue).
     * Insère dans commande puis dans commanderevue, dans une transaction.
     *
     * @param array|null $champs Données de l'abonnement
     * @return int|null 1 si succès, null en cas d'échec
     */
    private function insertCommandeRevue(?array $champs): ?int {
        if (empty($champs))
            return null;

        // Générer un id de 5 caractères si absent ou vide
        if (empty($champs["Id"]) && empty($champs["id"])) {
            $newId = str_pad((string)rand(1, 99999), 5, "0", STR_PAD_LEFT);
        } else {
            $newId = $champs["Id"] ?? $champs["id"];
        }

        $commande = [
            "id"           => $newId,
            "dateCommande" => $champs["Datecommande"] ?? $champs["datecommande"] ?? null,
            "montant"      => $champs["Montant"] ?? $champs["montant"] ?? null
        ];

        $cmdRevue = [
            "id"                => $newId,
            "iddocument"        => $champs["Iddocument"] ?? $champs["iddocument"] ?? null,
            "datecommande"      => $champs["Datecommande"] ?? $champs["datecommande"] ?? null,
            "montant"           => $champs["Montant"] ?? $champs["montant"] ?? null,
            "datefinabonnement" => $champs["Datefinabonnement"] ?? $champs["datefinabonnement"] ?? null
        ];

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = $this->insertOneTupleOneTable("commande", $commande);
        $ok2 = $this->insertOneTupleOneTable("commanderevue", $cmdRevue);

        if ($ok1 === 1 && $ok2 === 1) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }

    /**
     * Supprime une commande de document (livre ou DVD).
     * Supprime d'abord dans commandedocument (enfant), puis dans commande (parent).
     * @param array|null $champs Contient l'identifiant de la commande
     * @return int|null 1 si succès, null en cas d'échec
     */
    private function deleteCommandeDocument(?array $champs): ?int {
        $id = $champs["id"] ?? null;
        if (empty($id))
            return null;

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = $this->deleteTuplesOneTable("commandedocument", ["id" => $id]);
        $ok2 = $this->deleteTuplesOneTable("commande", ["id" => $id]);

        if ($ok1 !== null && $ok2 !== null) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }

    /**
     * Supprime un abonnement (commande de revue).
     * Supprime d'abord dans commanderevue (enfant), puis dans commande (parent).
     * @param array|null $champs Contient l'identifiant de la commande
     * @return int|null 1 si succès, null en cas d'échec
     */
    private function deleteCommandeRevue(?array $champs): ?int {
        $id = $champs["id"] ?? null;
        if (empty($id))
            return null;

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = $this->deleteTuplesOneTable("commanderevue", ["id" => $id]);
        $ok2 = $this->deleteTuplesOneTable("commande", ["id" => $id]);

        if ($ok1 !== null && $ok2 !== null) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }

    private function deleteRevue(?array $champs): ?int {
        $id = $champs["id"] ?? null;
        if (empty($id))
            return null;

        if ($this->countExemplaires($id) > 0)
            return null;
        if ($this->countAbonnementsRevue($id) > 0)
            return null;

        if (!$this->conn->beginTransaction())
            return null;

        $ok1 = $this->deleteTuplesOneTable("revue", ["id" => $id]);
        $ok2 = $this->deleteTuplesOneTable("document", ["id" => $id]);

        if ($ok1 !== null && $ok2 !== null) {
            $this->conn->commit();
            return 1;
        }
        $this->conn->rollBack();
        return null;
    }

    /**
     * Authentifie un utilisateur par login/mot de passe.
     * Retourne un tableau contenant les infos de l'utilisateur
     * (login, pwd, idservice, LibelleService) ou null si non trouvé.
     *
     * @param array|null $champs Doit contenir "login" et "pwd"
     * @return array|null
     */
    private function selectUtilisateur(?array $champs): ?array {
        if (empty($champs) || !isset($champs["login"]) || !isset($champs["pwd"])) {
            return null;
        }
        $requete = "SELECT u.login, u.pwd, u.idservice, s.libelle AS LibelleService
                    FROM utilisateur u
                    JOIN service s ON s.id = u.idservice
                    WHERE u.login = :login AND u.pwd = :pwd;";
        return $this->conn->queryBDD($requete, [
            "login" => $champs["login"],
            "pwd"   => $champs["pwd"]
        ]);
    }
}
