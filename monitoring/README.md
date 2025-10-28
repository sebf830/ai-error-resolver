# ai-error-resolver-monitoring

## Description
Tableau de bord de supervision des erreurs centralisées sur ai-error-resolver.
Permet aux interfaces abonnées au hub mercure : 
- de suivre en temps réel les nouvelles erreurs remontées par tous les services utilisant l'api ai-error-resolver.
- avoir une vue d'ensemble sur toutes les erreurs générées.


## Objectif
- Réduire le temps de réaction aux incidents
- Diminuer le temps alloué au débuggage
- Centraliser les informations critiques

## Tester 
Le fichier dashboard.html doit être servi sur localhost:8001
Les branchement à mercure et à l'api ai-error-resolver se font automatiquement.  
Renseigner les token mercure & api definis dans le symfony .env

## Améliorations possibles (prochainement?)
- Transformer une simple remontée d'erreur en un ticket opérationnel avec cycle de vie(avancement, responsable, observations..)
- Définir des droits de visibilité par service, par utilisateur
- Améliorer la performance du prompt.
- Créer des alertes (emails, slack, intranet..)

