var builder = WebApplication.CreateBuilder(args);

builder.Services.AddHostedService<MqttService>();

var app = builder.Build();

app.Run();