<?php

// File: src/Controllers/PaymentScheduleController.php

require_once __DIR__ . '/../Services/PaymentScheduleService.php';

class PaymentScheduleController
{
    protected $service;

    public function __construct()
    {
        $this->service = new PaymentScheduleService();
    }

    // GET /api/payment_schedules
    public function index()
    {
        echo json_encode($this->service->getAllSchedules());
    }

    // GET /api/payment_schedules/{id}
    public function show($id)
    {
        echo json_encode($this->service->getScheduleById($id));
    }

    // POST /api/payment_schedules
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $this->service->createSchedule($data);
        echo json_encode(['message' => 'Payment schedule created successfully', 'id' => $id]);
    }

    // PUT/PATCH /api/payment_schedules/{id}
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $this->service->updateSchedule($id, $data);
        echo json_encode(['message' => 'Payment schedule updated successfully']);
    }

    // DELETE /api/payment_schedules/{id}
    public function destroy($id)
    {
        $this->service->deleteSchedule($id);
        echo json_encode(['message' => 'Payment schedule deleted successfully']);
    }
}
