<?php if(!empty($path_list)): ?>    
    <div class="groupPath">
        <?php foreach($section->pathgroup as $path): ?> 
            <?php if($path->link!=''): ?>
                <a class="lnkPath" href="<?php echo $path->link ?>"><?php echo $path->name ?></a>
            <?php else: ?>
                <span class="lnkPath"><?php echo $path->name ?></span>
            <?php endif; ?>
            <?php if(!empty($path->separator)): ?>
                <span class="separPath"><?php echo $path->separator ?></span>
            <?php endif; ?>
        
<?php endforeach; ?>
    </div>
<?php endif; ?>
