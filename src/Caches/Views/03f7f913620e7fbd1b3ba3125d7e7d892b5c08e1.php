<!DOCTYPE html>
<html lang="<?php echo e(config('app.locale')); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e(Admin::title()); ?> <?php if($header): ?> | <?php echo e($header); ?><?php endif; ?></title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <?php if(!is_null($favicon = Admin::favicon())): ?>
        <link rel="shortcut icon" href="<?php echo e($favicon); ?>">
    <?php endif; ?>

    <?php echo Admin::css(); ?>


    <script src="<?php echo e(Admin::jQuery()); ?>"></script>
    <?php echo Admin::headerJs(); ?>

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body class="hold-transition <?php echo e(config('admin.skin')); ?> <?php echo e(join(' ', config('admin.layout'))); ?>">
<?php if($alert = config('admin.top_alert')): ?>
    <div style="text-align: center;padding: 5px;font-size: 12px;background-color: #ffffd5;color: #ff0000;">
        <?php echo $alert; ?>

    </div>
<?php endif; ?>
<div class="wrapper">
    <!-- Main Header -->
    
    
    <?php echo $__env->make('header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php echo $__env->make('aside', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

    <div class="content-wrapper" id="pjax-container" style="margin-left: 0px">
        <?php echo Admin::style(); ?>

        <div id="app">
            <?php if(!$hideHeader): ?>
                <section class="content-header">
                    <?php if(!$hideDescription): ?>
                        <h1>
                            <?php echo $header ?: trans('admin.title'); ?>

                            <small><?php echo $description ?: trans('admin.description'); ?></small>
                        </h1>
                    <?php endif; ?>
                <!-- breadcrumb start -->
                    <?php if($breadcrumb && !$hideBreadcrumb): ?>
                        <ol class="breadcrumb" style="margin-right: 30px;">
                            <li><a href="<?php echo e(admin_url('/')); ?>"><i class="fa fa-dashboard"></i>
                                    Home</a></li>
                            <?php $__currentLoopData = $breadcrumb; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($loop->last): ?>
                                    <li class="active">
                                        <?php if(\Illuminate\Support\Arr::has($item, 'icon')): ?>
                                            <i class="fa fa-<?php echo e($item['icon']); ?>"></i>
                                        <?php endif; ?>
                                        <?php echo e($item['text']); ?>

                                    </li>
                                <?php else: ?>
                                    <li>
                                        <?php if(\Illuminate\Support\Arr::has($item, 'url')): ?>
                                            <a href="<?php echo e(admin_url(\Illuminate\Support\Arr::get($item, 'url'))); ?>">
                                                <?php if(\Illuminate\Support\Arr::has($item, 'icon')): ?>
                                                    <i class="fa fa-<?php echo e($item['icon']); ?>"></i>
                                                <?php endif; ?>
                                                <?php echo e($item['text']); ?>

                                            </a>
                                        <?php else: ?>
                                            <?php if(\Illuminate\Support\Arr::has($item, 'icon')): ?>
                                                <i class="fa fa-<?php echo e($item['icon']); ?>"></i>
                                            <?php endif; ?>
                                            <?php echo e($item['text']); ?>

                                        <?php endif; ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ol>
                    <?php elseif(config('admin.enable_default_breadcrumb')): ?>
                        <ol class="breadcrumb" style="margin-right: 30px;">
                            <li><a href="<?php echo e(admin_url('/')); ?>"><i class="fa fa-dashboard"></i> <?php echo e(__('Home')); ?></a></li>
                            <?php for($i = 2; $i <= count(Request::segments()); $i++): ?>
                                <li>
                                    <?php echo e(ucfirst(Request::segment($i))); ?>

                                </li>
                            <?php endfor; ?>
                        </ol>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
            <section class="content" style="padding: 0px;">
                <?php echo $__env->make('alerts', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                <?php echo $__env->make('exception', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                <?php echo $__env->make('toastr', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                
                
                
                <?php if($_view_): ?>
                    
                    <?php echo $__env->make($_view_['view'], $_view_['data'], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                <?php else: ?>
                    <?php echo $_content_; ?>

                <?php endif; ?>
            </section>
        </div>
        <?php echo Admin::script(); ?>

        <?php echo Admin::html(); ?>

    </div>

    <?php if(!$hideFooter): ?>
        
        <?php echo $__env->make('footer', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php endif; ?>


</div>
<button id="totop" title="Go to top" style="display: none;"><i class="fa fa-chevron-up"></i></button>
<script>
    function LA() {
    }

    LA.token = "<?php echo e(csrf_token()); ?>";
    LA.user = <?php echo json_encode($_user_, 15, 512) ?>;
</script>
<?php echo Admin::js(); ?>

</body>
</html>

