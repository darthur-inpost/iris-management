	<div id="header"><img src="images/logo.png"/></div> 
	<div class="main_menu">
		<ul class="egmenu">
		<li><a href="summary.php">Summary</a></li>      
		<li><a href="javascript:void(0);" class="has-sub">Address To Locker</a>
		<ul>
			<li><a href="n_parcel.php">Create New</a></li>
			<li><a href="c_orders.php">View Created Parcels</a></li>
			<li><a href="d_orders.php">View Dispatched Parcels</a></li>
			<li><a href="c_many.php">Create Multiple</a></li>
		</ul>
		</li>
		<li><a href="javascript:void(0);" class="has-sub">Locker To Address</a>
		<ul>
			<li><a href="c_return.php">Create Simple</a></li>
			<li><a href="c_r_many.php">Create Multiple</a></li>
			<li><a href="r_orders.php">View Created Parcels</a></li>
			<li><a href="d_returns.php">View Dispatched Parcels</a></li>
		</ul>
		</li>
		<li><a href="search.php">Search</a></li>
		<li><a href="logout.php">Logout</a></li>
		<p style='text-align:right; margin-right:5px; color: orange;'><i><b><?php echo $username; ?></b></i></p>
		</ul>
	</div>
	<br>
