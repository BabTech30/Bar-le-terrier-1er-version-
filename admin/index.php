<?php
/**
 * LE TERRIER — Redirection vers le Dashboard
 * Charge directement le dashboard depuis le dossier parent
 */
chdir(dirname(__DIR__));
require __DIR__ . '/../index.php';
