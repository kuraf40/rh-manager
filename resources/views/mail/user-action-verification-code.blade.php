<p>Bonjour,</p>

<p>
    Utilisez le code ci-dessous pour
    @if ($action === 'create')
        confirmer l’adresse courriel de votre compte RH Manager
    @elseif ($action === 'email')
        confirmer cette nouvelle adresse courriel RH Manager
    @else
        confirmer le changement de mot de passe de votre compte RH Manager
    @endif
    :
</p>

<p style="font-size: 24px; font-weight: bold; letter-spacing: 6px;">{{ $code }}</p>

<p>Ce code expire dans 15 minutes. Si vous n’êtes pas à l’origine de cette demande, contactez l’administrateur.</p>

<p>RH Manager</p>
