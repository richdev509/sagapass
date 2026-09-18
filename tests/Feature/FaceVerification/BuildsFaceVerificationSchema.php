<?php

namespace Tests\Feature\FaceVerification;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schéma minimal (SQLite en mémoire, voir phpunit.xml) des seules tables que
 * touchent les tests de détection de doublons. Les vraies migrations ne
 * passent pas sur SQLite (SQL MySQL brut dans certaines, ordre `admins` /
 * permissions cassé sur une base vierge — problèmes pré-existants, hors
 * périmètre), d'où ce schéma reconstruit à la main, colonne par colonne
 * seulement celles réellement utilisées.
 */
trait BuildsFaceVerificationSchema
{
    protected function buildFaceVerificationSchema(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('developer_applications')) {
            Schema::create('developer_applications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('website')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('client_id')->nullable();
                $table->text('client_secret')->nullable();
                $table->text('app_key')->nullable();
                $table->text('webhook_secret')->nullable();
                $table->json('redirect_uris')->nullable();
                $table->json('allowed_scopes')->nullable();
                $table->string('status')->default('pending');
                $table->boolean('is_trusted')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('partner_verification_sessions')) {
            Schema::create('partner_verification_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('token')->unique();
                $table->unsignedBigInteger('developer_application_id');
                $table->string('partner_reference')->nullable();
                $table->json('partner_submitted_data')->nullable();
                $table->string('webhook_url')->nullable();
                $table->string('document_type');
                $table->string('front_photo_path')->nullable();
                $table->string('back_photo_path')->nullable();
                $table->string('selfie_left_path')->nullable();
                $table->string('selfie_center_path')->nullable();
                $table->string('selfie_right_path')->nullable();
                $table->string('status')->default('awaiting_id_capture');
                $table->float('face_match_score')->nullable();
                $table->boolean('liveness_passed')->nullable();
                $table->string('ocr_extracted_document_number')->nullable();
                $table->string('ocr_extracted_full_name')->nullable();
                $table->date('ocr_extracted_date_of_birth')->nullable();
                $table->json('analysis_raw')->nullable();
                $table->json('warnings')->nullable();
                $table->json('duplicate_check')->nullable();
                $table->text('pending_face_embedding')->nullable();
                $table->timestamp('consent_accepted_at')->nullable();
                $table->string('consent_terms_version')->nullable();
                $table->string('rejection_reason')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('partner_verified_identity_id')->nullable();
                $table->string('blacklist_hit')->nullable();
                $table->unsignedBigInteger('blacklist_matched_identity_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('partner_verified_identities')) {
            Schema::create('partner_verified_identities', function (Blueprint $table) {
                $table->id();
                $table->string('kyc_id')->unique();
                $table->unsignedBigInteger('developer_application_id');
                $table->string('document_type');
                $table->string('document_number');
                $table->string('full_name')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->float('face_match_score')->nullable();
                $table->boolean('liveness_passed')->nullable();
                $table->string('status')->default('valid');
                $table->timestamp('verified_at');
                $table->timestamp('valid_until');
                $table->string('webhook_url')->nullable();
                $table->unsignedBigInteger('last_partner_verification_session_id')->nullable();
                $table->timestamp('expired_notified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('blacklisted_identities')) {
            Schema::create('blacklisted_identities', function (Blueprint $table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('photo_path')->nullable();
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('added_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('blacklisted_identity_documents')) {
            Schema::create('blacklisted_identity_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('blacklisted_identity_id');
                $table->string('document_type')->nullable();
                $table->string('document_number');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('face_embeddings')) {
            Schema::create('face_embeddings', function (Blueprint $table) {
                $table->id();
                $table->text('embedding');
                $table->string('document_type');
                $table->string('document_number')->nullable();
                $table->string('full_name')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->unsignedBigInteger('developer_application_id')->nullable();
                $table->unsignedBigInteger('partner_verification_session_id')->nullable();
                $table->unsignedBigInteger('partner_verified_identity_id')->nullable();
                $table->timestamps();
            });
        }
    }
}
