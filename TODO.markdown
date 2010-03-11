
TODO List:
=========

* implement support for views:
  - switch view = false, setting in model as $this->view = true;
  - save disabled for view
  - creating of autofield disabled for view
* for now views excluded from dbsync, has to do manually
* implement setting lazyloading so that filter() works (when creating new model, lazyloading settings
  of parent model is forgoten
* implement fetch for filter(), there has to be new class QuerySetResult with arrayaccess support
* fill should move to PerfORM from QuerySets
* support to set condition for filter() also for related models, via model__anothermodel__field=value (as in Django)
  to be able to handle aliased names of joined tables
* support for sqlite2
* include support for more model relations as ManyToManyField (http://docs.djangoproject.com/en/1.1/ref/models/fields/#manytomanyfield)
  or OneToManyField (http://docs.djangoproject.com/en/1.1/ref/models/fields/#onetoonefield)
* using in real application and put to production
* filters 
* licensing



* implement sync for views, view's select will be defined in view, storage will keep
  hash of definition, when changed, view will be dropped and recreated
