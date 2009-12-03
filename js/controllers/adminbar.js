function onLoginClick() {
	if ($('adminbar').visible()) {
		Effect.BlindUp('adminbar', { duration: 0.4} );
	}
	else {
		Effect.BlindDown('adminbar', { duration: 0.4}  );
	}
}
