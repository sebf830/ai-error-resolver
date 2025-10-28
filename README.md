# ai-error-resolver

## Description
Ce service capture les erreurs internes ou distantes, les journalise, et diffuse instantanément une analyse/solution générée par ChatGPT à toutes les interfaces abonnées via Mercure..


## Fonctionalités
Capture automatique des exceptions Symfony via un event subscriber.  

Persistance des erreurs (message, trace, scénario d’exécution, contexte technique).  

Analyse automatique par ChatGPT pour obtenir une solution.  

Diffusion temps réel via le hub Mercure.  

Dashboard réactif affichant les erreurs au fur et à mesure de leur traitement


## Technique
Symfony 7+  

Messenger : workers pour le traitement asynchrone  

Mercure : push en temps réel des données vers le front  

OpenAPI : génération de solutions automatiques  

Alpine.js : Gestion du dashboard  


## Mise en place 
Lancer le conteneur mercure
```
docker-compose up -d 
```

Créer et peupler la bdd  

Lancer le worker amqp
```
php bin/console messenger:consume async
``` 

Servir le fichier /monitoring/dashboard.html sur un serveur distant
``` 
php -S localhost:8001 
python -m http.server 8001
```   


## Améliorations possibles (prochainement?)
- Transformer une simple remontée d'erreur en un ticket opérationnel avec cycle de vie(avancement, responsable, observations..)
- Définir des droits de visibilité par service, par utilisateur
- Améliorer la performance du prompt.
- Créer des alertes (emails, slack, intranet..)

