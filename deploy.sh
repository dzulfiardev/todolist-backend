#!/bin/bash

# Laravel 12 TodoList Docker Deployment Script
set -e

echo "🚀 Laravel 12 TodoList Docker Deployment"
echo "========================================"

# Function to print colored output
print_status() {
    echo -e "\033[1;34m$1\033[0m"
}

print_success() {
    echo -e "\033[1;32m✅ $1\033[0m"
}

print_error() {
    echo -e "\033[1;31m❌ $1\033[0m"
}

# Check if Docker and Docker Compose are installed
check_requirements() {
    print_status "Checking requirements..."
    
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    print_success "Docker and Docker Compose are installed"
}

# Build and start containers
build_and_start() {
    print_status "Building and starting containers..."
    
    # Stop existing containers
    docker-compose down 2>/dev/null || true
    
    # Build images
    docker-compose build --no-cache
    
    # Start containers
    docker-compose up -d
    
    print_success "Containers started successfully"
}

# Wait for services to be ready
wait_for_services() {
    print_status "Waiting for services to be ready..."
    
    # Wait for app container to be running
    timeout=60
    while [ $timeout -gt 0 ]; do
        if [ "$(docker-compose ps -q app 2>/dev/null)" ]; then
            if [ "$(docker inspect -f '{{.State.Running}}' $(docker-compose ps -q app) 2>/dev/null)" == "true" ]; then
                break
            fi
        fi
        sleep 2
        timeout=$((timeout-2))
    done
    
    if [ $timeout -le 0 ]; then
        print_error "Timeout waiting for app container to start"
        docker-compose logs app
        exit 1
    fi
    
    print_success "Services are ready"
}

# Run tests
run_tests() {
    print_status "Running tests..."
    
    # Wait a bit more for the application to be fully ready
    sleep 10
    
    if docker-compose exec -T app php artisan test; then
        print_success "All tests passed"
    else
        print_error "Some tests failed"
        return 1
    fi
}

# Show status and URLs
show_status() {
    print_status "Deployment Status:"
    echo ""
    
    # Show container status
    docker-compose ps
    
    echo ""
    print_success "🎉 Laravel 12 TodoList Application is ready!"
    echo ""
    echo "📱 Application URLs:"
    echo "   • API: http://localhost:8000"
    echo "   • Health: http://localhost:8000/api"
    echo "   • phpMyAdmin: http://localhost:8080"
    echo ""
    echo "🔧 Database Connection:"
    echo "   • Main DB: localhost:3307"
    echo "   • Test DB: localhost:3308"
    echo "   • Username: root"
    echo "   • Password: root"
    echo ""
    echo "📚 API Endpoints:"
    echo "   • GET  /api/todo-lists"
    echo "   • POST /api/todo-lists"
    echo "   • GET  /api/chart?type=status"
    echo "   • GET  /api/chart?type=priority"
    echo "   • GET  /api/chart?type=assignee"
    echo "   • GET  /api/reports/todo-lists/export"
    echo ""
    echo "🔍 Useful Commands:"
    echo "   • View logs: docker-compose logs -f app"
    echo "   • Run tests: docker-compose exec app php artisan test"
    echo "   • Access shell: docker-compose exec app bash"
    echo "   • Stop containers: docker-compose down"
}

# Main deployment function
deploy() {
    echo "Starting deployment..."
    
    check_requirements
    build_and_start
    wait_for_services
    
    # Give the application some time to fully initialize
    print_status "Waiting for application to initialize..."
    sleep 15
    
    # Run tests
    if ! run_tests; then
        print_error "Tests failed, but deployment continues"
    fi
    
    show_status
}

# Parse command line arguments
case "${1:-deploy}" in
    "deploy")
        deploy
        ;;
    "start")
        print_status "Starting existing containers..."
        docker-compose up -d
        wait_for_services
        show_status
        ;;
    "stop")
        print_status "Stopping containers..."
        docker-compose down
        print_success "Containers stopped"
        ;;
    "restart")
        print_status "Restarting containers..."
        docker-compose restart
        wait_for_services
        show_status
        ;;
    "logs")
        docker-compose logs -f app
        ;;
    "test")
        print_status "Running tests..."
        docker-compose exec app php artisan test
        ;;
    "shell")
        docker-compose exec app bash
        ;;
    "status")
        docker-compose ps
        ;;
    "clean")
        print_status "Cleaning up containers and volumes..."
        docker-compose down -v
        docker system prune -f
        print_success "Cleanup completed"
        ;;
    *)
        echo "Usage: $0 {deploy|start|stop|restart|logs|test|shell|status|clean}"
        echo ""
        echo "Commands:"
        echo "  deploy  - Build and deploy the application (default)"
        echo "  start   - Start existing containers"
        echo "  stop    - Stop containers"
        echo "  restart - Restart containers"
        echo "  logs    - View application logs"
        echo "  test    - Run tests"
        echo "  shell   - Access application shell"
        echo "  status  - Show container status"
        echo "  clean   - Clean up containers and volumes"
        exit 1
        ;;
esac