<?php
require_once __DIR__ . '/admin_access.php';
require_admin_access();
include("../../db.php");

$product_id = filter_var(
    isset($_POST['product_id']) ? $_POST['product_id'] : (isset($_GET['product_id']) ? $_GET['product_id'] : null),
    FILTER_VALIDATE_INT
);
if ($product_id === false || $product_id === null || $product_id < 1) {
    http_response_code(400);
    exit('Invalid product identifier.');
}
$csrf_token = admin_csrf_token();

$product_statement = mysqli_prepare($con, 'SELECT product_id, product_cat, product_brand, product_title, product_price, product_desc, product_image, product_keywords FROM products WHERE product_id = ?');
if ($product_statement === false || !mysqli_stmt_bind_param($product_statement, 'i', $product_id) || !mysqli_stmt_execute($product_statement)) {
    throw new RuntimeException('Unable to retrieve the requested product.');
}
$stored_product_id = $product_type = $brand = $product_name = $price = $details = $pic_name = $tags = null;
if (!mysqli_stmt_bind_result($product_statement, $stored_product_id, $product_type, $brand, $product_name, $price, $details, $pic_name, $tags)) {
    throw new RuntimeException('Unable to read the requested product.');
}
if (mysqli_stmt_fetch($product_statement) === false) {
    throw new RuntimeException('Unable to fetch the requested product.');
}
if ($stored_product_id === null) {
    http_response_code(404);
    exit('Product not found.');
}
mysqli_stmt_close($product_statement);

if(isset($_POST['btn_save']))
{
require_admin_csrf_token();
$product_name=$_POST['product_name'];
$details=$_POST['details'];
$price=$_POST['price'];
$product_type=$_POST['product_type'];
$brand=$_POST['brand'];
$tags=$_POST['tags'];

$price=$_POST['price']*73.77000;
//picture coding
$picture_name=$_FILES['picture']['name'];
$picture_type=$_FILES['picture']['type'];
$picture_tmp_name=$_FILES['picture']['tmp_name'];
$picture_size=$_FILES['picture']['size'];

if($picture_type=="image/jpeg" || $picture_type=="image/jpg" || $picture_type=="image/png" || $picture_type=="image/gif")
{
	if($picture_size<=50000000)
	
		$pic_name=time()."_".$picture_name;
        move_uploaded_file($picture_tmp_name,"../../product_images/".$pic_name);
        mysqli_query($con,"UPDATE `products` SET `product_title` = '$product_name', 
        `product_cat`= '$product_type',`product_brand`='$brand',
        `product_price`='$price',`product_desc`='$details',`product_image`='$pic_name',`product_keywords`='$tags' WHERE `product_id` = '$product_id'") or die("Query 2 is inncorrect..........");
    header("location: products_list.php");
    exit;
   
}else{
    mysqli_query($con,"UPDATE `products` SET `product_title` = '$product_name', 
        `product_cat`= '$product_type',`product_brand`='$brand',
        `product_price`='$price',`product_desc`='$details',`product_image`='$pic_name',`product_keywords`='$tags' WHERE `product_id` = '$product_id'") or die("Query 2 is inncorrect..........");
    header("location: products_list.php");
    exit;
}


mysqli_close($con);
}
include "sidenav.php";
include "topheader.php";
?>
      <!-- End Navbar -->
      <div class="content">
        <div class="container-fluid">
          <form action="" method="post" type="form" name="form" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="product_id" value="<?php echo (int) $product_id; ?>">
          <div class="row">
          
                
         <div class="col-md-7">
            <div class="card">
              <div class="card-header card-header-primary">
                <h5 class="title">Edit Product</h5>
              </div>
              <div class="card-body">
                
                  <div class="row">
                    
                    <div class="col-md-12">
                      <div class="form-group">
                        <label>Product Title</label>
                        <input type="text" id="product_name" required name="product_name" value="<?php echo $product_name; ?>" class="form-control">
                      </div>
                    </div>
                    
                    <div class="col-md-4">
                    <img src='<?php echo "../../product_images/".$pic_name?>' style='width:50px; height:50px; border:groove #000'>
                      <div class="">
                        <label for="">Add Image</label>
                        <input type="file" name="picture"  class="btn btn-fill btn-success" value="<?php echo "../../product_images/".$pic_name?>" id="picture" >
                      </div>
                    </div>
                     <div class="col-md-12">
                      <div class="form-group">
                        <label>Description</label>
                        <textarea rows="4" cols="80" id="details" required name="details"  class="form-control"><?php echo $details;?></textarea>
                      </div>
                    </div>
                  
                    <div class="col-md-12">
                      <div class="form-group">
                        <label>Pricing</label>
                        <input type="text" id="price" name="price" value="<?php echo $price ?>" required class="form-control" >
                      </div>
                    </div>
                  </div>
                 
                  
                
              </div>
              
            </div>
          </div>
          <div class="col-md-5">
            <div class="card">
              <div class="card-header card-header-primary">
                <h5 class="title">Categories</h5>
              </div>
              <div class="card-body">
                
                  <div class="row">
                  <div class="col-md-12">
                          <div class="form-group">
                            <label for="">Product Category</label>
                            <select id="product_type" name="product_type"  required class="form-control">
                            <option value="" style="color:black;">select Category</option>
                            <?php 
                                
                                    $result1=mysqli_query($con,"SELECT * FROM `categories` ORDER BY `cat_id` ASC") or die ("query 1 incorrect.....");

                                    while(list($cat_id,$cat_title)=mysqli_fetch_array($result1))
                                    {
                                    
                                        if($cat_id==$product_type){
                                            echo "<option value='$cat_id' style='color:black;' selected>$cat_title</option>";
                                        }else{
                                            echo "<option value='$cat_id' style='color:black;'>$cat_title</option>";
                                        }
                                    }
                                    
                                ?>
                            </select>
                        </div>
                      </div>
                      <div class="col-md-12">
                          <div class="form-group">
                            <label for="">Product Type</label>
                            <select id="brand" name="brand" required class="form-control">
                            <option value="" style="color:black;">select Type</option>
                                <?php
                                    $result2=mysqli_query($con,"SELECT * FROM `brands`") or die ("query 1 incorrect.....");

                                    while(list($brand_id,$brand_title)=mysqli_fetch_array($result2))
                                    {
                                        if($brand_id==$brand){
                                            echo "<option value='$brand_id' style='color:black;' selected>$brand_title</option>";
                                        }else{
                                            echo "<option value='$brand_id' style='color:black;'>$brand_title</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                      </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <label>Product Keywords</label>
                        <input type="text" id="tags" name="tags" value="<?php echo $tags; ?>" required class="form-control" >
                      </div>
                    </div>
                  </div>
                
              </div>
              <div class="card-footer">
                  <button type="submit" id="btn_save" name="btn_save" required class="btn btn-fill btn-primary">Update Product</button>
              </div>
            </div>
          </div>
          
        </div>
         </form>
          
        </div>
      </div>
      <?php
include "footer.php";
?>