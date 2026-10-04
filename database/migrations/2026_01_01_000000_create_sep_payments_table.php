<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(config('sep.database.table', 'sep_payments'), function (Blueprint $table): void {
            $table->id();
            $table->string('order_id', 100)->index();
            $table->unsignedBigInteger('amount');
            $table->string('res_num', 50)->unique();
            $table->string('token', 255)->nullable()->index();
            $table->string('ref_num', 50)->nullable()->unique();
            $table->string('rrn', 100)->nullable()->index();
            $table->string('trace_no', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('gateway_status', 100)->nullable();
            $table->unsignedBigInteger('wage')->nullable();
            $table->unsignedBigInteger('affective_amount')->nullable();
            $table->string('masked_pan', 32)->nullable();
            $table->string('hashed_pan', 128)->nullable();
            $table->unsignedBigInteger('verified_amount')->nullable();
            $table->integer('verify_result_code')->nullable();
            $table->text('verify_result_description')->nullable();
            $table->string('status', 40)->index();
            $table->text('failure_reason')->nullable();
            $table->json('failure_context')->nullable();
            $table->json('callback_payload')->nullable();
            $table->json('verify_payload')->nullable();
            $table->json('reverse_payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sep.database.table', 'sep_payments'));
    }
};
