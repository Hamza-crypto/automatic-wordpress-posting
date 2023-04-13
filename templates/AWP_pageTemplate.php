<h1>Automatic Wordpress Posting from CSV</h1>
<form id="awp_form" method="post" enctype="multipart/form-data">
    <div class="awp_row">
        <label for="awp_title"> Post title
            <input type="text" name="awp_title" id="awp_title" required placeholder="post_title"></label>
        <label for="awp_term_id"> Post category
            <select name="awp_term_id" id="awp_term_id" id="category">
                <?php
                $cats = get_terms('category', array(
                    'hide_empty'    => false,
                ));
                foreach($cats as $cat){
                    ?>
                    <option value="<?php echo $cat->term_id; ?>"><?php echo $cat->name; ?></option>
                    <?php
                }
                ?>
            </select></label>
        <label for="awp_per_day"> Row per day
            <input type="number" name="awp_per_day" id="awp_per_day" required value="10"></label>
    </div>
    <label for="awp_term_id"> Post content
    <textarea name="awp_template" id="awp_template" cols="30" rows="20" required placeholder="post_content">
    </textarea></label>
    <div class="awp_row_bottom">
        <label for="filescv">
            CSV file
            <input type="file" accept=".csv" name="awp_csv" id="filescv" required>
        </label>
        <label for="filethumb">
            Thumbnail for post ( png , jpg , jpeg )
            <input type="file" accept=".png,.jpg,.jpeg" id="filethumb" name="awp_thumbnail">
        </label>
    </div>
    <input type="submit">
    <input type="hidden" name="action" value="awp_upload_posts"/>
</form>
<div id="awp_result">
    <p></p>
</div>