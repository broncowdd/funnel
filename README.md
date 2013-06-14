funnel
======
bronco@warriordudimanche.net

Un script pour agréger plusieurs flux rss en un seul

On l'utilise en ajoutant les flux dans l'array $feeds de la partie config.
On peut également passer les url des flux à agréger en $_GET en les séparant d'un espace.
?feeds=url1 url2 url3

Funnel.php va récupérer chaque flux et en faire un seul en classant tous les items par date.
Un lien vers le flux d'origine est ajouté en fin de description ( [via xxxx] )

J'ai utilisé la librairie syndexport.php de http://milletmaxime.net/syndexport/