<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $notifiableUserClass = 'App\\Models\\User';

        if (! Schema::hasTable('notifications')) {
            return;
        }

        if (! Schema::hasColumn('notifications', 'user_id')) {
            return;
        }

        Schema::rename('notifications', 'legacy_notifications');

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_at_index');
        });

        DB::table('legacy_notifications')
            ->orderBy('id')
            ->chunkById(200, function ($legacyNotifications) use ($notifiableUserClass): void {
                $rowsToInsert = [];

                foreach ($legacyNotifications as $legacyNotification) {
                    $legacyData = json_decode((string) $legacyNotification->data, true);
                    $legacyData = is_array($legacyData) ? $legacyData : [];
                    $message = $legacyData['message'] ?? null;
                    $context = $legacyData['context'] ?? [];

                    $rowsToInsert[] = [
                        'id' => (string) Str::uuid(),
                        'type' => \Filament\Notifications\DatabaseNotification::class,
                        'notifiable_type' => $notifiableUserClass,
                        'notifiable_id' => (int) $legacyNotification->user_id,
                        'data' => json_encode([
                            'actions' => [],
                            'body' => is_string($message) ? $message : null,
                            'duration' => 'persistent',
                            'format' => 'filament',
                            'title' => is_string($message) ? $message : 'Notification',
                            'viewData' => [
                                'context' => is_array($context) ? $context : [],
                                'notification_type' => (string) $legacyNotification->type,
                            ],
                        ]),
                        'read_at' => $legacyNotification->read_at,
                        'created_at' => $legacyNotification->created_at,
                        'updated_at' => $legacyNotification->updated_at,
                    ];
                }

                if ($rowsToInsert !== []) {
                    DB::table('notifications')->insert($rowsToInsert);
                }
            });

        Schema::drop('legacy_notifications');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $notifiableUserClass = 'App\\Models\\User';

        if (! Schema::hasTable('notifications')) {
            return;
        }

        if (! Schema::hasColumn('notifications', 'notifiable_type')) {
            return;
        }

        Schema::rename('notifications', 'database_notifications');

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        DB::table('database_notifications')
            ->where('notifiable_type', $notifiableUserClass)
            ->orderBy('created_at')
            ->chunk(200, function ($databaseNotifications): void {
                $rowsToInsert = [];

                foreach ($databaseNotifications as $databaseNotification) {
                    $databaseData = json_decode((string) $databaseNotification->data, true);
                    $databaseData = is_array($databaseData) ? $databaseData : [];
                    $viewData = $databaseData['viewData'] ?? [];
                    $legacyType = is_array($viewData) ? ($viewData['notification_type'] ?? null) : null;
                    $message = $databaseData['body'] ?? $databaseData['title'] ?? null;
                    $context = is_array($viewData) ? ($viewData['context'] ?? []) : [];

                    $rowsToInsert[] = [
                        'user_id' => (int) $databaseNotification->notifiable_id,
                        'type' => is_string($legacyType) ? $legacyType : (string) $databaseNotification->type,
                        'data' => json_encode([
                            'message' => is_string($message) ? $message : 'Notification',
                            'context' => is_array($context) ? $context : [],
                        ]),
                        'read_at' => $databaseNotification->read_at,
                        'created_at' => $databaseNotification->created_at,
                        'updated_at' => $databaseNotification->updated_at,
                    ];
                }

                if ($rowsToInsert !== []) {
                    DB::table('notifications')->insert($rowsToInsert);
                }
            });

        Schema::drop('database_notifications');
    }
};
