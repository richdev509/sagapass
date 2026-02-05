<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de Confidentialité - Sagaloto WhatsApp Bot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            color: #075E54;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .last-updated {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 30px;
        }

        h2 {
            color: #075E54;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.5em;
            border-bottom: 2px solid #25D366;
            padding-bottom: 5px;
        }

        h3 {
            color: #128C7E;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        p {
            margin-bottom: 15px;
            text-align: justify;
        }

        ul {
            margin-bottom: 15px;
            padding-left: 30px;
        }

        li {
            margin-bottom: 8px;
        }

        .highlight {
            background-color: #E7F8F3;
            padding: 15px;
            border-left: 4px solid #25D366;
            margin: 20px 0;
        }

        .contact-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 0.9em;
        }

        strong {
            color: #075E54;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 Politique de Confidentialité</h1>
        <p class="last-updated"><strong>Dernière mise à jour :</strong> {{ now()->format('d/m/Y') }}</p>

        <div class="highlight">
            <p><strong>En bref :</strong> Le service WhatsApp de Sagaloto est un outil interne réservé aux administrateurs autorisés pour consulter des rapports et des statistiques d'activité. Aucune donnée personnelle de tiers n'est collectée ou traitée.</p>
        </div>

        <h2>1. Introduction</h2>
        <p>
            Bienvenue sur le service WhatsApp de <strong>Sagaloto</strong>. Cette politique de confidentialité décrit comment nous collectons, utilisons et protégeons les informations lorsque vous interagissez avec notre bot WhatsApp.
        </p>
        <p>
            Ce service est exclusivement destiné à un <strong>usage interne</strong> par les administrateurs autorisés de Sagaloto pour accéder à des rapports d'activité, des résumés de ventes et des informations statistiques via WhatsApp.
        </p>

        <h2>2. Informations que Nous Collectons</h2>

        <h3>2.1 Informations Automatiquement Collectées</h3>
        <p>Lorsque vous utilisez notre service WhatsApp, nous collectons automatiquement :</p>
        <ul>
            <li><strong>Numéro de téléphone WhatsApp</strong> : Pour identifier l'utilisateur et vérifier l'autorisation d'accès</li>
            <li><strong>Messages envoyés</strong> : Les commandes et interactions avec le bot</li>
            <li><strong>Horodatage</strong> : Date et heure de chaque interaction</li>
            <li><strong>Métadonnées</strong> : Informations techniques sur les messages (ID de message, statut de livraison)</li>
        </ul>

        <h3>2.2 Informations que Nous Ne Collectons PAS</h3>
        <ul>
            <li>❌ Données de localisation</li>
            <li>❌ Contacts de votre téléphone</li>
            <li>❌ Médias (photos, vidéos, documents) non sollicités</li>
            <li>❌ Informations personnelles sensibles</li>
        </ul>

        <h2>3. Utilisation des Informations</h2>
        <p>Les informations collectées sont utilisées uniquement pour :</p>
        <ul>
            <li><strong>Fournir le service</strong> : Répondre à vos demandes de rapports et d'informations</li>
            <li><strong>Sécurité</strong> : Vérifier que seuls les administrateurs autorisés accèdent au service</li>
            <li><strong>Amélioration du service</strong> : Analyser les interactions pour améliorer les fonctionnalités</li>
            <li><strong>Logs d'audit</strong> : Maintenir un historique des actions pour des raisons de traçabilité et de conformité</li>
        </ul>

        <h2>4. Accès aux Données</h2>

        <h3>4.1 Utilisateurs Autorisés</h3>
        <p>
            Seuls les <strong>administrateurs Sagaloto dont le numéro WhatsApp est préalablement autorisé</strong> peuvent accéder au service. Toute tentative d'accès par un numéro non autorisé est automatiquement rejetée et enregistrée.
        </p>

        <h3>4.2 Partage des Données</h3>
        <p>Nous ne vendons, ne louons ni ne partageons vos informations avec des tiers, sauf :</p>
        <ul>
            <li><strong>WhatsApp (Meta)</strong> : Comme fournisseur de la plateforme de messagerie</li>
            <li><strong>Obligation légale</strong> : Si requis par la loi ou une autorité compétente</li>
        </ul>

        <h2>5. Sécurité des Données</h2>
        <p>Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos informations :</p>
        <ul>
            <li>🔒 <strong>Chiffrement</strong> : Communications via l'API WhatsApp Cloud sécurisée (HTTPS)</li>
            <li>🔑 <strong>Authentification</strong> : Vérification HMAC des webhooks pour garantir l'authenticité</li>
            <li>🛡️ <strong>Liste blanche</strong> : Accès restreint aux numéros de téléphone autorisés uniquement</li>
            <li>📊 <strong>Logs sécurisés</strong> : Stockage des logs d'audit dans une base de données protégée</li>
            <li>⏱️ <strong>Sessions temporaires</strong> : Les sessions utilisateur expirent automatiquement après 15 minutes d'inactivité</li>
        </ul>

        <h2>6. Conservation des Données</h2>
        <p>Nous conservons les données pour les durées suivantes :</p>
        <ul>
            <li><strong>Sessions actives</strong> : Jusqu'à 15 minutes d'inactivité</li>
            <li><strong>Logs de messages</strong> : {{ config('app.env') === 'production' ? '90 jours' : '30 jours' }} pour des raisons d'audit et de sécurité</li>
            <li><strong>Liste des numéros autorisés</strong> : Tant que l'administrateur est actif</li>
        </ul>
        <p>Les données obsolètes sont automatiquement supprimées conformément à notre politique de rétention.</p>

        <h2>7. Vos Droits</h2>
        <p>En tant qu'utilisateur autorisé, vous avez le droit de :</p>
        <ul>
            <li>✅ <strong>Accéder</strong> à vos données personnelles</li>
            <li>✅ <strong>Rectifier</strong> vos informations si elles sont inexactes</li>
            <li>✅ <strong>Supprimer</strong> votre compte et vos données</li>
            <li>✅ <strong>Retirer votre consentement</strong> à tout moment</li>
            <li>✅ <strong>Demander une copie</strong> de vos données</li>
        </ul>

        <h2>8. Cookies et Technologies Similaires</h2>
        <p>
            Le service WhatsApp utilise des <strong>sessions en cache</strong> (stockées temporairement en mémoire ou en base de données) pour maintenir le contexte de la conversation. Aucun cookie n'est déposé sur votre appareil.
        </p>

        <h2>9. Services Tiers</h2>
        <p>Notre service utilise :</p>
        <ul>
            <li><strong>WhatsApp Business Cloud API</strong> (Meta Platforms, Inc.) : Pour l'envoi et la réception de messages</li>
            <li>Politique de confidentialité de Meta : <a href="https://www.whatsapp.com/legal/privacy-policy" target="_blank">https://www.whatsapp.com/legal/privacy-policy</a></li>
        </ul>

        <h2>10. Protection des Mineurs</h2>
        <p>
            Ce service est <strong>strictement réservé aux administrateurs majeurs</strong> de Sagaloto dans le cadre de leurs fonctions professionnelles. Il n'est pas destiné à être utilisé par des mineurs.
        </p>

        <h2>11. Modifications de Cette Politique</h2>
        <p>
            Nous nous réservons le droit de modifier cette politique de confidentialité à tout moment. Les modifications entreront en vigueur dès leur publication sur cette page. La date de "Dernière mise à jour" sera actualisée en conséquence.
        </p>
        <p>
            Les utilisateurs autorisés seront informés des changements importants via WhatsApp.
        </p>

        <h2>12. Juridiction et Loi Applicable</h2>
        <p>
            Cette politique de confidentialité est régie par les lois de la <strong>République d'Haïti</strong>. Tout litige sera soumis aux tribunaux compétents d'Haïti.
        </p>

        <h2>13. Contact</h2>
        <div class="contact-info">
            <p>Pour toute question concernant cette politique de confidentialité ou vos données personnelles, contactez-nous :</p>
            <ul>
                <li><strong>Email :</strong> contact@mykaypa.com</li>
                <li><strong>Service :</strong> Sagaloto - Support Technique</li>
                <li><strong>Adresse :</strong> Haïti</li>
            </ul>
            <p><strong>Responsable de la protection des données :</strong> Équipe Technique Sagaloto</p>
        </div>

        <h2>14. Consentement</h2>
        <p>
            En utilisant le service WhatsApp de Sagaloto, vous reconnaissez avoir lu, compris et accepté cette politique de confidentialité.
        </p>

        <div class="footer">
            <p><strong>© {{ now()->year }} Sagaloto</strong> - Tous droits réservés</p>
            <p>Service WhatsApp Bot - Usage Interne Administrateurs</p>
        </div>
    </div>
</body>
</html>
