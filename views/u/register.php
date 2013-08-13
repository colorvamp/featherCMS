	<form class="registerForm" method="post">
		<input type="hidden" name="subcommand" value="userRegister"/>
		<div class="whiteBox middleInd">
			<h3>Register a new user</h3>
			<p>Register a new user</p>
			<table class="form"><tbody>
				<tr><td>Name</td><td><input class="text" type="text" name="userName" placeholder="user name"/></td></tr>
				<tr><td>Mail</td><td><input class="text" type="text" name="userMail" placeholder="user mail"/></td></tr>
				<tr><td class="separator" colspan="2"></td></tr>
				<tr><td>Password</td><td><input class="text" type="password" name="userPass" placeholder="password"/></td></tr>
				<tr><td>Repeat Password</td><td><input class="text" type="password" name="userPassR" placeholder="repeat password"/></td></tr>
			</tbody></table>
			<div class="btn-group"><button class="btn">Register</button></div>
		</div>
	</form>
