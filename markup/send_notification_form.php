<form id="mp_send" method="POST" class="uk-form">

<div class="uk-card uk-card-body">
<div>
		<label class="InputfieldHeader uk-form-label" for="mps_user">Subscribed users</label>
		<select id="mps_user" name="mps_user" class="uk-select">
			<option value="0">Select a user</option>

			<?php

			$subscribed_users = $mp->subscribed_users();

			// Will need to get names and set an order I guess...
			foreach ($subscribed_users as $subscribed_user) {

				echo '<option value="' . $subscribed_user['user_key'] . '">' . $users->get($subscribed_user['user_key'])->name . '</option>';
			}

			?>
		</select>
	</div>
	<div>
		<label class="InputfieldHeader uk-form-label" for="mps_content">Notification</label>
		<textarea id="mps_content" name="mps_content" class="uk-textarea" required></textarea>
		<p>Note: we strip any tags and returns out of this message so don't get carried away</p>
	</div>
		<button class="uk-button uk-button-primary" type="submit">Send this message</button>
	</div>


</form>