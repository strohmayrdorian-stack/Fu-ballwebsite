# Fu-ballwebsite
////////////////////////////
//// USER & COMMUNITY  ////
////////////////////////////
Table users {
id integer [pk, increment]
username varchar(50) [not null, unique]
password_hash varchar(255) [not null]
role varchar(20) [not null, note: 'admin, manager, user']
created_at timestamp
}


////////////////////////////
//// FOOTBALL STRUCTURE ////
////////////////////////////
Table league {
id int [pk, increment]
name varchar(100) [not null]
country varchar(100)
}
Table season {
id int [pk, increment]
name varchar(50) [not null, note: '2024/2025']
league_id int [not null]
start_date date
end_date date
}
Ref: season.league_id > league.id

////////////////////////////
//// TEAMS & PLAYERS    ////
////////////////////////////
Table team {
id int [pk, increment]
name varchar(100) [not null]
founded_year int
stadium_id int
logo varchar(100)
}
Table stadium {
id int [pk, increment]
name varchar(120)
city varchar(100)
capacity int
}
Ref: team.stadium_id > stadium.id
/* Team participates in a specific season */
Table team_season {
id int [pk, increment]
season_id int [not null]
team_id int [not null]
}
Ref: team_season.team_id > team.id
Ref: team_season.season_id > season.id

Table player {
id int [pk, increment]
name varchar(120) [not null]
birthdate date
nationality varchar(80)
}
/* Player plays for a team in a season */
Table player_season {
id int [pk, increment]
player_id int [not null]
team_season_id int [not null]
shirt_number int
position varchar(30)
}
Ref: player_season.player_id > player.id
Ref: player_season.team_season_id > team_season.id

////////////////////////////
//// MATCHES & RESULTS ////
////////////////////////////
Table match {
id int [pk, increment]
season_id int [not null]
home_team_season_id int [not null]
away_team_season_id int [not null]
stadium_id int
kickoff timestamp
home_goals int
away_goals int
}
Ref: match.season_id > season.id
Ref: match.home_team_season_id > team_season.id
Ref: match.away_team_season_id > team_season.id
Ref: match.stadium_id > stadium.id

////////////////////////////
//// MATCH EVENTS       ////
////////////////////////////
Table match_event {
id int [pk, increment]
player_season_id int [not null]
match_id int [not null]
minute int
event_type varchar(20) [note: 'goal, assist, yellow_card, red_card, own_goal, penalty_goal']
}
Ref: match_event.match_id > match.id
Ref: match_event.player_season_id > player_season.id