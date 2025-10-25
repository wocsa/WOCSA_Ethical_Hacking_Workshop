========[WOCSA]X[C’estVraiCa]==============================================
# Table des matières
1. [Introduction (20-30 min)](#introduction-20-30-min)
2. [Activité 1 : Vérification d'images (30-40 min)](#activité-1-vérification-dimages-30-40-min)
3. [Activité 2 : Vérification d'articles (45-60 min)](#activité-2-vérification-darticles-45-60-min)
4. [Conclusion (20-30 min)](#conclusion-20-30-min)
---
# Introduction (20-30 min)
## Objectif :
Sensibiliser les participants à l'importance de la vérification des faits et du démenti, et leur donner un aperçu des enjeux.
L'objectif de l'atelier est d'apprendre à douter des informations que nous rencontrons.

## Présentation des Associations :
- **Wocsa** : [https://www.wocsa.org/](https://www.wocsa.org/)
  - WOCSA : Améliorer la compréhension des enjeux de cybersécurité pour tous types de publics, en utilisant des solutions OpenSource. L'atelier Ethical Hacking se concentre sur l'apprentissage de la défense par la pratique des attaques.
- **C’est vrai ça ?** : [https://cestvraica.com/](https://cestvraica.com/)
  - C’est vrai ça ? : Prouver de manière rigoureuse si une publication est vraie, fausse, ou un mélange des deux. Les analyses et démentis se concentrent principalement sur le réseau social [https://www.linkedin.com/](https://www.linkedin.com/).

## Définitions :
- **Qu’est-ce qu’une fake news (infox en français) ?**
  - « Information fausse, biaisée ou délibérément trompeuse, diffusée par exemple pour favoriser un parti politique au détriment d’un autre, nuire à la réputation d’une personne ou d’une entreprise, ou contredire une vérité scientifique établie, contribuant ainsi à la désinformation du public. »
  - Source : [https://www.pyrenees-atlantiques.gouv.fr/](https://www.pyrenees-atlantiques.gouv.fr/)
- **Qu’est-ce que la désinformation ?**
  - « Action particulière ou continue qui consiste, en utilisant tous les moyens, à induire en erreur un adversaire ou à encourager la subversion afin de l’affaiblir ; résultat de cette action. »
  - Source : [https://www.cnrtl.fr/definition/academie9/désinformation](https://www.cnrtl.fr/definition/academie9/d%C3%A9sinformation)
- **Qu’est-ce que la mésinformation ?**
  - « Information fausse qui n’est pas partagée avec l’intention de nuire. »
  - Source : [https://www.coe.int/fr/web/campaign-free-to-speak-safe-to-learn/dealing-with-propaganda-misinformation-and-fake-news](https://www.coe.int/fr/web/campaign-free-to-speak-safe-to-learn/dealing-with-propaganda-misinformation-and-fake-news)
  - C’est ce que l’on trouve le plus sur les réseaux sociaux : des publications aimées et partagées par des mésinformateurs... qui n’ont pas fait attention à ce qu’ils partageaient.
  - « En cas de doute, on vérifie... et parfois, on ne partage pas. »
- **Qu’est-ce qu’une information malveillante ?**
  - « Information basée sur des faits réels, utilisée pour causer du tort. »
  - Source : [https://www.coe.int/fr/web/campaign-free-to-speak-safe-to-learn/dealing-with-propaganda-misinformation-and-fake-news](https://www.coe.int/fr/web/campaign-free-to-speak-safe-to-learn/dealing-with-propaganda-misinformation-and-fake-news)
- **Qu’est-ce que le debunking ?**
  - « Ridiculiser, discréditer. »
  - Source : [https://www.larousse.fr/dictionnaires/anglais-francais/debunk/574303](https://www.larousse.fr/dictionnaires/anglais-francais/debunk/574303)
  - Ici, le debunking s’applique aux fausses informations.

## Biais cognitifs :
- Facteurs de propagation des fake news : [https://fr.wikipedia.org/wiki/Biais_cognitif](https://fr.wikipedia.org/wiki/Biais_cognitif)
- [https://prebunking.withgoogle.com/fr/](https://prebunking.withgoogle.com/fr/) autres biais à montrer « en images » ici.
- **Biais sensorimoteurs** : En matière de processus sensorimoteurs, on parle généralement d’illusions plutôt que de biais.
- **Biais attentionnels** :
  - Biais attentionnel — avoir des perceptions influencées par ses propres intérêts.
- **Biais de mémoire** :
  - Effet de récence — mieux se souvenir de la dernière information à laquelle on a été exposé.
  - Effet de simple exposition — le fait d’avoir été précédemment exposé à une personne ou une situation la rend plus positive.
- **Biais de jugement** :
  - Appel à la probabilité — tendance à considérer quelque chose comme vrai parce que cela pourrait probablement être le cas.
  - Appel à la tradition — tendance à considérer que l’ancienneté d’une théorie ou d’une assertion soutient sa véracité.
  - Biais d’ancrage — influence de la première impression.
  - Biais d’automatisation — favoriser l’avis de la machine à celui de l’humain.
  - Biais de confirmation — tendance à valider ses opinions avec des sources qui les confirment, et à rejeter immédiatement celles qui les infirment.
  - Effet Dunning-Kruger — les moins compétents dans un domaine surestiment leur compétence, tandis que les plus compétents tendent à sous-estimer la leur.
- **Biais de raisonnement** :
  - Biais de confirmation d’hypothèse — préférer les éléments qui confirment plutôt que ceux qui infirment une hypothèse.
  - Biais de disponibilité — ne pas chercher d’informations au-delà de ce qui est immédiatement disponible.
  - Illusion de regroupement — percevoir des coïncidences dans des données aléatoires.
  - Coûts irrécupérables — prendre en compte les coûts déjà engagés dans une décision.

## Chiffres clés :
- « Rapport Reuters 2025 : Méfiance record, chatbots émergents, vidéos sociales triomphantes » — [https://larevuedesmedias.ina.fr/rapport-reuters-2025-videos-reseaux-sociaux-chatbots-presse](https://larevuedesmedias.ina.fr/rapport-reuters-2025-videos-reseaux-sociaux-chatbots-presse)
  - 29 % de confiance dans les médias (41e mondial).
  - 44 % des 18-24 ans utilisent les réseaux sociaux et les plateformes vidéo comme principales sources d’information.
  - 36 % de la population française et 40 % de la population mondiale évitent les actualités : l’effet négatif des nouvelles sur l’humeur, leur caractère épuisant, la surabondance de politique ou de conflits.
- « Élections européennes 2024 : les Français particulièrement vulnérables à la désinformation » — [https://www.ipsos.com/fr-fr/europeennes-2024/europeennes-2024-les-francais-particulierement-vulnerables-la-desinformation](https://www.ipsos.com/fr-fr/europeennes-2024/europeennes-2024-les-francais-particulierement-vulnerables-la-desinformation)
  - 74 % des répondants estiment pouvoir distinguer le vrai du faux sur les réseaux sociaux... mais 68 % pensent que ce n’est pas le cas pour le reste de la population française.
  - 66 % des répondants croient à au moins une des fake news qui leur sont présentées.
  - Alors que 61 % font encore confiance à la presse écrite, les médias en ligne ne sont crédibles que pour 35 % des répondants.
- « Lutter contre la propagande, la désinformation et les fake news » — [https://www.coe.int/fr/web/campaign-free-to-speak-safe-to-learn/dealing-with-propaganda-misinformation-and-fake-news](https://www.coe.int/fr/web/campaign-free-to-speak-safe-to-learn/dealing-with-propaganda-misinformation-and-fake-news)
  - La moitié des citoyens européens âgés de 15 à 30 ans déclarent avoir besoin d’informations et de compétences en analyse critique pour les aider à combattre les fake news et l’extrémisme dans la société.

---
# Activité 1 : Vérification d'images (30-40 min)
## Objectif :
Apprendre à distinguer les images réelles de celles qui sont modifiées ou générées par IA, en utilisant des outils de vérification.
Avant l’exercice, utilisez ce jeu pour voir si les participants peuvent détecter les images générées par IA : [https://realitycheckk.com/week1](https://realitycheckk.com/week1) (sans outils d’aide).

## Matériel nécessaire :
- Banque d’images imprimées (réelles, modifiées, générées par IA).
- Fiches outils : Google Lens, TinEye, FotoForensics, Hive Moderation, etc.
- Grilles de critères pour évaluer l’authenticité.

## Outils :
- Recherche d’images déjà publiées en ligne : [https://tineye.com/](https://tineye.com/)
- Manipulation et accès aux métadonnées : [https://fotoforensics.com/](https://fotoforensics.com/)
- Détection d’images IA : [https://sightengine.com/detecter-images-generees-par-ia](https://sightengine.com/detecter-images-generees-par-ia)
- Détection d’images IA : [https://hivemoderation.com/ai-generated-content-detection](https://hivemoderation.com/ai-generated-content-detection)

## Banque d’images :
- **Photos modifiées et/ou générées par IA** :
  - [https://cestvraica.com/debunk/1409119756744790046](https://cestvraica.com/debunk/1409119756744790046) --> \[photo_1.webp\](./Ressources_:_Part_1_-_Images/photo_1.webp)
  - [https://cestvraica.com/debunk/1408215809490419762](https://cestvraica.com/debunk/1408215809490419762) --> \[photo_2.webp\](./Ressources_:_Part_1_-_Images/photo_2.webp)
  - [https://cestvraica.com/debunk/1404440017656479874](https://cestvraica.com/debunk/1404440017656479874) --> \[photo_3.webp\](./Ressources_:_Part_1_-_Images/photo_3.webp)
  - [https://cestvraica.com/debunk/1403360878161690826](https://cestvraica.com/debunk/1403360878161690826) --> \[photo_4.webp\](./Ressources_:_Part_1_-_Images/photo_4.webp) && \[photo_4_bis.webp\](./Ressources_Part_1_-_Images/photo_3.webp)
  - [https://cestvraica.com/debunk/1400378195965907055](https://cestvraica.com/debunk/1400378195965907055) --> \[photo_5.webp\](./Ressources_:_Part_1_-_Images/photo_5.webp) && \[photo_5_bis.webp\](./Ressources_Part_1_-_Images/photo_3.webp)
  - [https://cestvraica.com/debunk/1375761178529239070](https://cestvraica.com/debunk/1375761178529239070) --> \[photo_6.webp\](./Ressources_:_Part_1_-_Images/photo_6.webp)
- **Photos réelles** :
  - [https://cestvraica.com/debunk/1406962306222391390](https://cestvraica.com/debunk/1406962306222391390) --> \[photo_7.webp\](./Ressources_:_Part_1_-_Images/photo_7.webp)
  - [https://cestvraica.com/debunk/1404926199171387567](https://cestvraica.com/debunk/1404926199171387567) --> \[photo_8.webp\](./Ressources_:_Part_1_-_Images/photo_8.webp)
  - [https://cestvraica.com/debunk/1404204307498139841](https://cestvraica.com/debunk/1404204307498139841) --> \[photo_9.webp\](./Ressources_:_Part_1_-_Images/photo_9.webp)
  - [https://cestvraica.com/debunk/1402917917095366722](https://cestvraica.com/debunk/1402917917095366722) --> \[photo_10.webp\](./Ressources_:_Part_1_-_Images/photo_10.webp)
  - [https://cestvraica.com/debunk/1401806496672514132](https://cestvraica.com/debunk/1401806496672514132) --> \[photo_11.webp\](./Ressources_:_Part_1_-_Images/photo_11.webp)
  - [https://cestvraica.com/debunk/1401807715235266690](https://cestvraica.com/debunk/1401807715235266690) --> \[photo_12.webp\](./Ressources_:_Part_1_-_Images/photo_12.webp)
  - [https://cestvraica.com/debunk/1402767921066934272](https://cestvraica.com/debunk/1402767921066934272) --> \[photo_13.webp\](./Ressources_:_Part_1_-_Images/photo_13.webp)

## Déroulement :
- **Présentation des outils (5 min)** :
  - Montrer comment utiliser Google Lens, TinEye, ... pour vérifier une image.
  - Expliquer les limites de chaque outil (ex. : les images générées par IA peuvent tromper les détecteurs).
- **Jeu en équipe (20 min)** :
  - Les participants sont divisés en X équipes de deux ou trois personnes.
  - Chaque équipe reçoit un ensemble d’images et doit les classer en 2 catégories : réelles, modifiées/générées par IA.
  - Ils doivent justifier leurs choix à l’aide des outils et des grilles de critères. \[grille_evaluation_images.pdf\](./Ressources_:_Part_1_-_Images/image_evaluation_grid.pdf)
- **Débat et correction (15 min)** :
  - Chaque équipe présente ses résultats pour la photo.
  - Débat entre les équipes sur les critères de choix. Quelles sont les limites rencontrées dans l’utilisation des outils ?
  - Explication de la bonne réponse avec des explications détaillées (ex. : analyse des métadonnées, incohérences dans les ombres, nombre d’orteils, etc.).

---
# Activité 2 : Vérification d'articles (45-60 min)
## Objectif :
Développer une méthodologie pour vérifier des articles et des sources, en équipe.

## Matériel nécessaire :
- 10 articles variés (mélange de vrais, faux et ambigus), avec différents niveaux de difficulté.
- Fiches outils : Vrai ou Faux (FranceInfo), À la Loupe (Toute l’Europe), Decodex (Le Monde), etc.
- Grilles d’analyse : critères de crédibilité (source, auteur, date, preuves, biais, etc.).

## Outils :
- Base de données de démentis d’articles : [https://www.hoaxbuster.com/](https://www.hoaxbuster.com/)
- Google Dorking : [https://gist.github.com/sundowndev/283efaddbcf896ab405488330d1bbc06](https://gist.github.com/sundowndev/283efaddbcf896ab405488330d1bbc06)
- Internet (évidemment) : [google.com](https://google.com)
- Google Fact Check Explorer : [https://toolbox.google.com/factcheck/explorer](https://toolbox.google.com/factcheck/explorer)
- InVID : [https://www.invid-project.eu/](https://www.invid-project.eu/)
- Snopes : [https://www.snopes.com/](https://www.snopes.com/)

## Banque d’articles :
- **Articles faux** :
  - [https://cestvraica.com/debunk/1421115781223026761](https://cestvraica.com/debunk/1421115781223026761) --> \[article1_steve_jobs.pdf\](./Ressources_:_Part_2_-_Articles/article1_steve_jobs.pdf)
  - [https://cestvraica.com/debunk/1420714484833718355](https://cestvraica.com/debunk/1420714484833718355) --> \[article2_poison_antidote.pdf\](./Ressources_:_Part_2_-_Articles/article2_poison_antidote.pdf)
  - [https://cestvraica.com/debunk/1419277487351009301](https://cestvraica.com/debunk/1419277487351009301) --> \[article3_couple_randonneurs.pdf\](./Ressources_:_Part_2_-_Articles/article3_couple_randonneurs.pdf)
- **Articles ambigus** :
  - [https://cestvraica.com/debunk/1419709249419743347](https://cestvraica.com/debunk/1419709249419743347) --> \[article4_drainage_postural.pdf\](./Ressources_:_Part_2_-_Articles/article4_drainage_postural.pdf)
  - [https://cestvraica.com/debunk/1419389900012261447](https://cestvraica.com/debunk/1419389900012261447) --> (vidéo)
  - [https://cestvraica.com/debunk/1414296994536755341](https://cestvraica.com/debunk/1414296994536755341) --> \[article5_tomate_francaise.pdf\](./Ressources_:_Part_2_-_Articles/article5_tomate_francaise.pdf)
- **Articles vrais** :
  - [https://cestvraica.com/debunk/1417979556878356571](https://cestvraica.com/debunk/1417979556878356571) --> \[article6_anne_hidalgo.pdf\](./Ressources_:_Part_2_-_Articles/article6_anne_hidalgo.pdf)
  - [https://cestvraica.com/debunk/1416129502378987621](https://cestvraica.com/debunk/1416129502378987621) --> \[article7_plongeur_decompression.pdf\](./Ressources_:_Part_2_-_Articles/article7_plongeur_decompression.pdf)
  - [https://cestvraica.com/debunk/1417557429649936444](https://cestvraica.com/debunk/1417557429649936444) --> \[article8_vittel_nestle.pdf\](./Ressources_:_Part_2_-_Articles/article8_vittel_nestle.pdf)
  - [https://cestvraica.com/debunk/1416051945558507534](https://cestvraica.com/debunk/1416051945558507534) --> \[article9_ia_albanie.pdf\](./Ressources_:_Part_2_-_Articles/article9_ia_albanie.pdf)

## Déroulement :
- **Présentation des outils et de la méthodologie (10 min)** :
  - Expliquer comment évaluer un article (qui, quoi, où, quand, pourquoi, comment).
  - Montrer des exemples de vérification avec les outils mentionnés.
- **Jeu en équipe (30 min)** :
  - Deux équipes s’affrontent : chaque équipe doit identifier le plus possible de fausses informations dans les 10 articles, en justifiant leurs choix.
  - Ils utilisent la grille pour se justifier : \[grille_evaluation_articles.pdf\](./Ressources_:_Part_2_-_Articles/article_evaluation_grid.pdf)
- **Débat et correction (15 min)** :
  - Chaque équipe présente ses conclusions.
  - Animer un débat sur les stratégies utilisées et les pièges rencontrés.
  - Révéler les bonnes réponses avec des explications (ex. : analyse des sources, recoupement, recherche d’experts).

---
# Conclusion (20-30 min)
## Synthèse des apprentissages :
- Résumer les outils et méthodes appris.
- Insister sur l’importance de la vigilance et de la curiosité.

## Ressources pour aller plus loin :
- Liste de sites et outils de fact-checking.
- Livres, podcasts, chaînes YouTube sur le sujet.

## Évaluation et retour :
- Questionnaire rapide pour évaluer la compréhension et l’appréciation de l’atelier.
- Discussion ouverte sur des ateliers complémentaires (ex. : deepfakes, théories du complot).

