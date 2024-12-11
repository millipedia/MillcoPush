# MillcoPush

This is a ProcessWire module to allow users to subscribe to push notifications in their browsers or as part of a PWA. 
It's being built for a members' only Citizen Science project we manage which has pretty specific requirements, and it's really not finished yet, but it might be of some use as a starting point if you need to do something similar.

It uses the [web-push-php](https://github.com/web-push-libs/web-push-php) library and currently leans heavily on the example javascript from that repo.

At the moment you need to be logged in to subscribe to notifications (cos that's what I need it to do), but it probably could work with guest users with a bit of fiddling.


## Keys

as lifted from the web-push-api readme, to create your keys you can do:

	openssl ecparam -genkey -name prime256v1 -out private_key.pem
	openssl ec -in private_key.pem -pubout -outform DER|tail -c 65|base64|tr -d '=' |tr '/+' '_-' >> public_key.txt
	openssl ec -in private_key.pem -outform DER|tail -c +8|head -c 32|base64|tr -d '=' |tr '/+' '_-' >> private_key.txt

These then need to be added to the module config.

## Service worker

As well as installing the module in the normal way you'll need to add the following javascript for a service worker to a serviceWorker.js file in the root of your site:

	self.addEventListener('push', function (event) {
		if (!(self.Notification && self.Notification.permission === 'granted')) {
			return;
		}

		const sendNotification = body => {

			// you could refresh a notification badge here with postMessage API
			const title = "Testlab";

			return self.registration.showNotification(title, {
				body,
			});
		};

		if (event.data) {
			const payload = event.data.json();
			event.waitUntil(sendNotification(payload.message));
		}
	});

Current versions of mobile Safari require the site to be installed to the phone's desktop so you'll also need a site.webmanifest file
Something like:

	{
		"name": "PWAPP",
		"short_name": "PWAPP",
		"icons": [
			{
				"src": "/android-chrome-192x192.png",
				"sizes": "192x192",
				"type": "image/png"
			},
			{
				"src": "/android-chrome-512x512.png",
				"sizes": "512x512",
				"type": "image/png"
			}
		],
		"theme_color": "#ffffff",
		"background_color": "#ffffff",
		"display": "standalone",
		"scope": "/",
		"start_url": "/",
		"display": "standalone",
		"handle_links": "preferred"
	}


## Add a subscribe button

On a front end template you need to render the subscribe button and associated js using:

	$mp=$modules->get('MillcoPush');
	echo $mp->markup_subs_butts();

That should probably be on a members only page whilst we require a user ID.

## Send a message

There's a very (very) simple form to select a user that has subscribed and send them a message in the Setup->Push page in the PW admin. Hopefully there will be more soon.

In the API you can send to an array of user ids using 

		/** @var MillcoPush $millcopush **/
		$millcopush=wire('modules')->getModule('MillcoPush');

		// send notifications
		$millcopush->send_push_notification_to_users($users,$message, $url);

or to a single user using

		$success=$mp->notify_user($recipient_id, $message);


stephen at [millipedia.com](https://millipedia.com)