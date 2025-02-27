import React, { useState, useEffect, useRef } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { Table, Card, Input, Button, Space, Tooltip } from "antd";
import axios from "axios";
import moment from "moment";

const columns = [
    {
        title: "Nama Pasien",
        dataIndex: "NAMAPASIEN",
        key: "NAMAPASIEN",
        fixed: "left",
    },
    {
        title: "Nomer RM",
        dataIndex: "PRWIKD_PASIEN",
        key: "PRWIKD_PASIEN",
        fixed: "left",
    },
    {
        title: "DPJP",
        dataIndex: "FMDDOKTERN",
        key: "FMDDOKTERN",
        fixed: "left",
    },
    {
        title: "Tanggal Masuk",
        dataIndex: "PRWITGL_MASUK",
        key: "PRWITGL_MASUK",
        render: (text) => moment(text).format("D-M-YYYY")
    },
    {
        title: "Tanggal Keluar",
        dataIndex: "PRWITGL_KELUAR",
        key: "PRWITGL_KELUAR",
        render: (text) => (text ? moment(text).format("D-M-YYYY") : ""),
    },
    {
        title: "Diagnosa Utama",
        dataIndex: "DIAGNOSA",
        key: "DIAGNOSA",
        render: (text) => <div dangerouslySetInnerHTML={{ __html: text }} />,
    },
    {
        title: "Diagnosa Sekunder",
        dataIndex: "DPJP",
        key: "DPJP",
    },
    {
        title: "Tindakan",
        dataIndex: "DPJP",
        key: "DPJP",
    },
    {
        title: "Total Hari",
        dataIndex: "TOTAL_HARI",
        key: "TOTAL_HARI",
    },
    {
        title: "Pemeriksaan Penunjang",
        dataIndex: "LOS",
        key: "DPJP",
    },
    {
        title: "Hasil Penunjang",
        dataIndex: "LOS",
        key: "DPJP",
    },
    {
        title: "Total Billing",
        dataIndex: "LOS",
        key: "DPJP",
    },
    {
        title: "Perkiraan Klaim",
        dataIndex: "LOS",
        key: "DPJP",
    },
    {
        title: "Hak Kelas",
        dataIndex: "LOS",
        key: "DPJP",
    },
    {
        title: "Naik Kelas",
        dataIndex: "LOS",
        key: "DPJP",
    },
    {
        title: "Kemungkinan Dignosis",
        dataIndex: "LOS",
        key: "DPJP",
    },
    {
        title: "Kemungkinan Prosedur",
        dataIndex: "LOS",
        key: "DPJP",
    },
];

export default function Index({ auth, pasiens }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-lg text-gray-800 leading-tight">
                    Pasien Ranap
                </p>
            }
        >
            <Head title="Pasien Ranap" />
            {JSON.stringify(pasiens)}
            <Card title="Pasien Ranap">
                <Table
                    pagination={false}
                    dataSource={pasiens}
                    columns={columns}
                    size="small"
                    rowKey="PRWINO_TRANSAKSI"
                    scroll={{ x: "max-content" }}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
