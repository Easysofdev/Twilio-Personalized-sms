<?php

/**
 * All public routes listed here. No middleware will not affect these routes
 */

use Illuminate\Support\Facades\Route;

Route::get('contacts/{contact}/subscribe-url', 'Customer\ContactsController@subscribeURL')->name('contacts.subscribe_url');
Route::post('contacts/{contact}/subscribe-url', 'Customer\ContactsController@insertContactBySubscriptionForm');
Route::any('dlr/twilio', 'Customer\DLRController@dlrTwilio')->name('dlr.twilio');
Route::any('inbound/twilio', 'Customer\DLRController@inboundTwilio')->name('inbound.twilio');

Route::any('dlr/callr', 'Customer\DLRController@dlrCallr')->name('dlr.callr');
Route::any('inbound/callr', 'Customer\DLRController@inboundCallr')->name('inbound.callr');

Route::any('inbound/bandwidth', 'Customer\DLRController@inboundBandwidth')->name('inbound.bandwidth');

Route::any('inbound/solucoesdigitais', 'Customer\DLRController@inboundSolucoesdigitais')->name('inbound.solucoesdigitais');
