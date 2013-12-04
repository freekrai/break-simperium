function generateUUID() {
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = (d + Math.random()*16)%16 | 0;
        d = Math.floor(d/16);
        return (c=='x' ? r : (r&0x7|0x8)).toString(16);
    });
    return uuid;
};
var myuuid = generateUUID();

// Load the application once the DOM is ready, using `jQuery.ready`:
$(function(){
	app_id = 'authorities-platforms-ed8';
		simperium = new Simperium( app_id, {
		token: '59a9eff00c80457686aece62a0047e2c'
	} );
	todoBucket = simperium.bucket( 'utest2' );
	
	// Todo Model
	// ----------
	
	// Our basic **Todo** model has `text`, `order`, and `done` attributes.
	var Todo = Backbone.Model.extend({	
		// Default attributes for the todo item.
		defaults: function() {
			return {
				text: "empty todo...",
				order: Todos.nextOrder(),
				done: false
			};
		},
		// Toggle the `done` state of this todo item.
		toggle: function() {
			this.save({done: !this.get("done")});
		}
	});
	
	// Todo Collection
	// ---------------
	
	// The collection of todos is backed by *localStorage* instead of a remote
	// server.
	var TodoList = Backbone.SimperiumCollection.extend({
		// Reference to this collection's model.
		model: Todo,
		// Save all of the todo items under the `"todos-backbone"` namespace.
		localStorage: new Backbone.LocalStorage("todos-backbone"),
		// Filter down the list of all todo items that are finished.
		done: function() {
			return this.where({done: true});
		},
		// Filter down the list to only todo items that are still not finished.
		remaining: function() {
			return this.where({done: false});
		},
		// We keep the Todos in sequential order, despite being saved by unordered
		// GUID in the database. This generates the next order number for new items.
		nextOrder: function() {
			if (!this.length) return 1;
			return this.last().get('order') + 1;
		},
		// Todos are sorted by their original insertion order.
		comparator: 'order'
	});
	
	var Todos = new TodoList( [], { bucket: todoBucket } );
	// Todo Item View
	// --------------
	
	// The DOM element for a todo item...
	var TodoView = Backbone.View.extend({
		
		//... is a list tag.
		tagName:  "li",
		
		// Cache the template function for a single item.
		template: _.template($('#item-template').html()),
		
		// The DOM events specific to an item.
		events: {
		},
		
		// The TodoView listens for changes to its model, re-rendering. Since there's
		// a one-to-one correspondence between a **Todo** and a **TodoView** in this
		// app, we set a direct reference on the model for convenience.
		initialize: function() {
			this.listenTo(this.model, 'change', this.render);
			this.listenTo(this.model, 'destroy', this.remove);
		},
		// Re-render the texts of the todo item.
		render: function() {
			this.$el.html(this.template(this.model.toJSON()));

console.log( myuuid + ' --- ' + this.model.get('text') );

//			this.$el.toggleClass('done', this.model.get('done'));
//			this.input = this.$('.edit');
			return this;
		},
		// Toggle the `"done"` state of the model.
		toggleDone: function() {
			this.model.toggle();
		},
		// Switch this view into `"editing"` mode, displaying the input field.
		edit: function() {
			this.$el.addClass("editing");
			this.input.focus();
		},
		// Close the `"editing"` mode, saving changes to the todo.
		close: function() {
			var value = this.input.val();
			if (!value) {
				this.clear();
			} else {
				this.model.save({text: value});
				this.$el.removeClass("editing");
			}
		},
		// If you hit `enter`, we're through editing the item.
		updateOnEnter: function(e) {
			if (e.keyCode == 13) this.close();
		},
		// Remove the item, destroy the model.
		clear: function() {
			this.model.destroy();
		}
	});

	// The Application
	// ---------------
	// Our overall **AppView** is the top-level piece of UI.
	var AppView = Backbone.View.extend({
		// Instead of generating a new element, bind to the existing skeleton of
		// the App already present in the HTML.
		el: $("#todoapp"),
		
		// Our template for the line of statistics at the bottom of the app.
		statsTemplate: _.template($('#stats-template').html()),
		
		// Delegated events for creating new items, and clearing completed ones.
		events: {
		},
		
		// At initialization we bind to the relevant events on the `Todos`
		// collection, when items are added or changed. Kick things off by
		// loading any preexisting todos that might be saved in *localStorage*.
		initialize: function() {
			this.listenTo(Todos, 'add', this.addOne);
			this.listenTo(Todos, 'reset', this.addAll);
			this.listenTo(Todos, 'all', this.render);
			
			this.footer = this.$('footer');
			this.main = $('#main');
			
			Todos.fetch();
		},
		// Re-rendering the App just means refreshing the statistics -- the rest
		// of the app doesn't change.
		render: function() {
			var done = Todos.done().length;
			var remaining = Todos.remaining().length;
			if (Todos.length) {
				this.main.show();
				this.footer.show();
				this.footer.html(this.statsTemplate({done: done, remaining: remaining}));
			} else {
				this.main.hide();
				this.footer.hide();
			}
//			this.allCheckbox.checked = !remaining;
		},
		// Add a single todo item to the list by creating a view for it, and
		// appending its element to the `<ul>`.
		addOne: function(todo) {
			var view = new TodoView({model: todo});
			this.$("#todo-list").append(view.render().el);
		},
		// Add all items in the **Todos** collection at once.
		addAll: function() {
			Todos.each(this.addOne, this);
		},
		// If you hit return in the main input field, create new **Todo** model,
		// persisting it to *localStorage*.
		createOnEnter: function(e) {
			if (e.keyCode != 13) return;
			if (!this.input.val()) return;
			
			Todos.create({text: this.input.val()});
			this.input.val('');
		},
		// Clear all done todo items, destroying their models.
		clearCompleted: function() {
			_.invoke(Todos.done(), 'destroy');
			return false;
		},
		toggleAllComplete: function () {
//			var done = this.allCheckbox.checked;
//			Todos.each(function (todo) { todo.save({'done': done}); });
		}
	});
	
	// Finally, we kick things off by creating the **App**.
	var App = new AppView;

});
