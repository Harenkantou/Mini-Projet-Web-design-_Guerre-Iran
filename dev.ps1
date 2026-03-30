param(
    [Parameter(Position = 0)]
    [ValidateSet("up", "down", "restart", "logs", "ps", "rebuild")]
    [string]$Action = "up"
)

switch ($Action) {
    "up" {
        docker compose up --build -d
    }
    "down" {
        docker compose down
    }
    "restart" {
        docker compose down
        docker compose up --build -d
    }
    "logs" {
        docker compose logs -f
    }
    "ps" {
        docker compose ps
    }
    "rebuild" {
        docker compose build --no-cache
    }
}
