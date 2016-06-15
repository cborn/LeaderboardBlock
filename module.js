M.block_leaderboard = {};

M.block_leaderboard.init_tabview = function(Y) {
    Y.use("tabview", function(Y) {
        var tabview = new Y.TabView({srcNode:'#leaderboard-tabs'});
        tabview.render();
    });
};