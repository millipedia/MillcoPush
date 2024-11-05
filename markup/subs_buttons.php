<?php

namespace ProcessWire;

/**
 * Write out some js that adds a subscribe button
 * 
 * Very much based on the webpush example code at the moment.
 */

?>

<button id="mp_subscribe_butt" class="butt" >Starting service worker...</button>

<?php

// If you have MillcoUtils installed then
// page has a nonce attached for use in CSP.
// $page->nonce

?>

<script <?= ($page->nonce ? "nonce='{$page->nonce}'" : '') ?>>
	// this is our public_key. Should be stored in module config
	const applicationServerKey = '<?= $public_key ?>';
	const class_prefix="mp_";
	// valid states we set as classes on our butt.
	const class_states=['enabled', 'disabled','computing','incompatible'];
	const pushButton = document.querySelector('#mp_subscribe_butt');

	let isPushEnabled = false;




	document.addEventListener('DOMContentLoaded', () => {

		if (!pushButton) {
			console.log("no butts");
			return;
		}

		pushButton.addEventListener('click', function() {
			if (isPushEnabled) {
				push_unsubscribe();
			} else {
				push_subscribe();
			}
		});

		if (!('serviceWorker' in navigator)) {
			console.warn('Service workers are not supported by this browser');
			changePushButtonState('incompatible');
			return;
		}

		if (!('PushManager' in window)) {
			console.warn('Push notifications are not supported by this browser');
			changePushButtonState('incompatible');
			return;
		}

		if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
			console.warn('Notifications are not supported by this browser');
			changePushButtonState('incompatible');
			return;
		}

		// Check the current Notification permission.
		// If its denied, the button should appears as such, until the user changes the permission manually
		if (Notification.permission === 'denied') {
			console.warn('Notifications are denied by the user');
			changePushButtonState('incompatible');
			return;
		}

		// We need to register the service worker at the root
		navigator.serviceWorker.register('/serviceWorker.js').then(
			() => {
				console.log('[SW] Service worker has been registered');
				push_updateSubscription();
			},
			e => {
				console.error('[SW] Service worker registration failed', e);
				changePushButtonState('incompatible');
			}
		);

		function changePushButtonState(state) {

			switch (state) {
				case 'enabled':
					pushButton.disabled = false;
					pushButton.textContent = 'Disable Push notifications';
					isPushEnabled = true;
					break;
				case 'disabled':
					pushButton.disabled = false;
					pushButton.textContent = 'Enable Push notifications';
					isPushEnabled = false;
					break;
				case 'computing':
					pushButton.disabled = true;
					pushButton.textContent = 'Loading...';
					break;
				case 'incompatible':
					pushButton.disabled = true;
					pushButton.textContent = 'Push notifications are not compatible with this browser';
					break;
				default:
					console.error('Unhandled push button state', state);
					break;
			}

			state_class_set(state);

		}

		/**
		 * Loop through our statuses and remove that class
		 * from our button, then add the new class.
		 */

		function state_class_set(state){

			console.log("in change state");

			class_states.forEach(status => {

				let mp_class=class_prefix + status;

				pushButton.classList.remove(mp_class);

				});

			let mp_class_to_add=class_prefix + state;
			pushButton.classList.add(mp_class_to_add);

		}

		function urlBase64ToUint8Array(base64String) {
			const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
			const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');

			const rawData = window.atob(base64);
			const outputArray = new Uint8Array(rawData.length);

			for (let i = 0; i < rawData.length; ++i) {
				outputArray[i] = rawData.charCodeAt(i);
			}
			return outputArray;
		}

		function checkNotificationPermission() {
			return new Promise((resolve, reject) => {
				if (Notification.permission === 'denied') {
					return reject(new Error('Push messages are blocked.'));
				}

				if (Notification.permission === 'granted') {
					return resolve();
				}

				if (Notification.permission === 'default') {
					return Notification.requestPermission().then(result => {
						if (result !== 'granted') {
							reject(new Error('Bad permission result'));
						} else {
							resolve();
						}
					});
				}

				return reject(new Error('Unknown permission'));
			});
		}

		function push_subscribe() {

			changePushButtonState('computing');

			return checkNotificationPermission()
				.then(() => navigator.serviceWorker.ready)
				.then(serviceWorkerRegistration =>
					serviceWorkerRegistration.pushManager.subscribe({
						userVisibleOnly: true,
						applicationServerKey: urlBase64ToUint8Array(applicationServerKey),
					})
				)
				.then(subscription => {
					// Subscription was successful
					// create subscription on your server
					return push_sendSubscriptionToServer(subscription, 'POST');
				})
				.then(subscription => subscription && changePushButtonState('enabled')) // update your UI
				.catch(e => {
					if (Notification.permission === 'denied') {
						// The user denied the notification permission which
						// means we failed to subscribe and the user will need
						// to manually change the notification permission to
						// subscribe to push messages
						console.warn('Notifications are denied by the user.');
						changePushButtonState('incompatible');
					} else {
						// A problem occurred with the subscription; common reasons
						// include network errors or the user skipped the permission
						console.error('Impossible to subscribe to push notifications', e);
						changePushButtonState('disabled');
					}
				});
		}

		function push_updateSubscription() {
			navigator.serviceWorker.ready
				.then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
				.then(subscription => {
					changePushButtonState('disabled');

					if (!subscription) {
						// We aren't subscribed to push, so set UI to allow the user to enable push
						return;
					}

					// Keep your server in sync with the latest endpoint
					return push_sendSubscriptionToServer(subscription, 'PUT');
				})
				.then(subscription => subscription && changePushButtonState('enabled')) // Set your UI to show they have subscribed for push messages
				.catch(e => {
					console.error('Error when updating the subscription', e);
				});
		}

		function push_unsubscribe() {

			changePushButtonState('computing');

			// To unsubscribe from push messaging, you need to get the subscription object
			navigator.serviceWorker.ready
				.then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
				.then(subscription => {
					// Check that we have a subscription to unsubscribe
					if (!subscription) {
						// No subscription object, so set the state
						// to allow the user to subscribe to push
						changePushButtonState('disabled');
						return;
					}

					// We have a subscription, unsubscribe
					// Remove push subscription from server
					return push_sendSubscriptionToServer(subscription, 'DELETE');
				})
				.then(subscription => subscription.unsubscribe())
				.then(() => changePushButtonState('disabled'))
				.catch(e => {
					// We failed to unsubscribe, this can lead to
					// an unusual state, so it may be best to remove
					// the users data from your data store and
					// inform the user that you have done so
					console.error('Error when unsubscribing the user', e);
					changePushButtonState('disabled');
				});
		}



		/** send our subscription to the server */
		function push_sendSubscriptionToServer(subscription, method) {

			const key = subscription.getKey('p256dh');
			const token = subscription.getKey('auth');
			const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];

			return fetch('/millcopush/subscription/manage/', {
				method: 'POST',
				body: JSON.stringify({
					action: method,
					endpoint: subscription.endpoint,
					publicKey: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
					authToken: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
					contentEncoding,
				}),
			}).then(() => subscription);



		}


	});
</script>