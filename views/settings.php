<div class="wrap">
	<h1 class="wp-heading-inline">Credentials</h1>

	<form method="post" action="matomo-site-kit.php" novalidate="novalidate">

		<table class="form-table" role="presentation">
			<tbody>

			<tr>
				<th scope="row">
					<label for="host">Host</label>
				</th>
				<td>
					<input type="text" name="host" id="host" class="regular-text"
					       placeholder="https://matomo.my-website.com" required>
					<p class="description">How to get my Matomo host name ?</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="idsite">Site id</label>
				</th>
				<td>
					<input type="number" min="1" max="999" step="1" name="idsite" id="idsite" class="regular-number"
					       value="1">
					<p class="description">How to get my ID Site ?</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="idcontainer">Container id</label>
				</th>
				<td>
					<input type="number" minlength="8" maxlength="8" name="idcontainer" id="idcontainer" class="regular-text"
					       value="1">
					<p class="description">How to get my ID Site ?</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="host">API key</label>
				</th>
				<td>
					<input type="text" name="host" id="host" class="regular-text">
					<p class="description">How to get my Matomo API Key ?</p>
				</td>
			</tr>

			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary"
			       value="Enregistrer les modifications">
		</p>

	</form>
</div>