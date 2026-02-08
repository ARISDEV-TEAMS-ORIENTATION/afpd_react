<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;
$u = User::where('email','test@example.com')->first();
if ($u) {
    echo $u->id . '|' . $u->email . '|' . $u->statut . PHP_EOL;
} else {
    echo "NOT_FOUND" . PHP_EOL;
}
