<?php defined('BASEPATH') OR exit('No direct script access allowed');//TODO : translate help file// inline help html. Only 'help_body' is used.$lang['help_body'] = "<h4>Introduction</h4><p>Le module Navigation permet de contrôler la navigation principale du site ainsi que les autres groupes de navigation.</p><h4>Groupe de navigation</h4><p>Les liens de navigation sont affichés en fonction de leur groupe de liens. Dans la plupart des thèmes le groupe Header est le groupe de navigation principale.Consultez la documentation du thème pour connaître les groupes de navigation disponibles pour le thème.pour afficher un groupe sur votre site utilisez le tag suivant: {{ navigation:links group=\"your-group-name\" }}</p><h4>Ajouter des liens</h4><p>Choisissez un titre pour votre lien, puis sélectionnez le groupe dans lequel il devra apparaître.Type de liens disponibles:<ul><li>URL: un lien externe - http://google.com</li><li>Lien du site: un lien vers une page du site - galleries/portfolio-pictures</li><li>Module: lien vers la page d\'accueil d'un module</li><li>Page: lien vers une page</li></ul>Target indique si le lien doit s\'ouvrir dans un autre onglet ou une autre fenêtre. (Astuce : utilisez l'option Nouvelle fenêtre avec parcimonie pour éviter d'agacer vos visiteurs.)Le champ class permet d\'ajouter une classe CSS au lien.</p><p></p><h4>Gérer l'ordre d\'affichage des liens</h4><p>L'ordre d\'affichage des liens sue le site reflète l'ordre d\'affichage dans le panneau d\'administration.Pout changer l\'ordre d\'affichage glisser-déposer les liens pour correspondre à ce que vous souhaitez.</p>";