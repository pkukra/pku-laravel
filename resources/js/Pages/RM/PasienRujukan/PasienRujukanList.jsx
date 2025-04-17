import React, { useState, useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { Card, Button, Table, Row, Col, DatePicker, Input } from "antd";
import axios from "axios";
import moment from "moment";
import dayjs from "dayjs";

export default function Index({ auth }) {
    const queryParams = new URLSearchParams(window.location.search);

    const initialPage = parseInt(queryParams.get("page")) || 1;
    const initialPerPage = parseInt(queryParams.get("per_page")) || 100;
    const initialDate =
        queryParams.get("date") || moment().format("YYYY-MM-DD");
    const initialKodePoly = queryParams.get("kode_poly") || "";
    const initialKodeDokter = queryParams.get("kode_dokter") || "";
    const initialNoRM = queryParams.get("no_rm") || ""; // Add for No RM filter

    const [loading, setLoading] = useState(false);
    const [dataPasienRujukans, setDataPasienRujukans] = useState([]);
    const [totalData, setTotalData] = useState(0);
    const [page, setPage] = useState(initialPage);
    const [perPage, setPerPage] = useState(initialPerPage);
    const [date, setDate] = useState(initialDate);
    const [kodePoly, setKodePoly] = useState(initialKodePoly);
    const [kodeDokter, setKodeDokter] = useState(initialKodeDokter);
    const [noRM, setNoRM] = useState(initialNoRM); // Add state for No RM filter

    const columnsRujukan = [
        {
            title: "Tgl Jam Periksa",
            dataIndex: "FRPTGL",
            render: (_, record) => (
                <>
                    {moment(record.FRPTGL).format("DD/MM/YYYY")}{" "}
                    {moment(record.FRPJAM).format("HH:mm")}
                </>
            ),
        },
        {
            title: "No RM / Nama Pasien",
            dataIndex: "NAMAPASIEN",
            render: (_, record) => (
                <>
                    {record.FRPPASIEN_ID} /<br />
                    {record.NAMAPASIEN}
                </>
            ),
        },
        {
            title: "Kode Poly",
            dataIndex: "FRPUNIT",
            key: "FRPUNIT",
        },
        {
            title: "Nama Poly",
            dataIndex: "FMPKLINIKN",
            key: "FMPKLINIKN",
        },
        {
            title: "Kode Dokter",
            dataIndex: "FRPDOKTER_ID",
            key: "FRPDOKTER_ID",
        },
        {
            title: "Dokter",
            dataIndex: "FMDDOKTERN",
            key: "FMDDOKTERN",
        },
        {
            title: "Kelompok",
            dataIndex: "FRPCUSTOMER_ID",
            key: "FRPCUSTOMER_ID",
        },
        {
            title: "Action",
            dataIndex: "action",
            key: "action",
            render: (_, record) => (
                <a
                    href={route("rm.pasien-rujukan.detail", {
                        kode_reg: record.FRPNOTRANSAKSIKJ,
                    })}
                >
                    <Button type="primary" size="small">
                        Tampilkan
                    </Button>
                </a>
            ),
        },
    ];

    const fetchDataPasienRujukan = async (
        pageVal = page,
        perPageVal = perPage
    ) => {
        setLoading(true);
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.list_rujukan_data"),
                {
                    params: {
                        date,
                        page: pageVal,
                        per_page: perPageVal,
                        kode_poly: kodePoly,
                        kode_dokter: kodeDokter,
                        no_rm: noRM, // Pass the No RM filter
                    },
                }
            );
            setDataPasienRujukans(response?.data?.data?.data || []);
            setTotalData(response?.data?.data?.total || 0);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
            setLoading(false);
        }
    };

    const handleTableChange = (pagination) => {
        const newPage = pagination.current;
        const newPerPage = pagination.pageSize;

        const params = new URLSearchParams(window.location.search);
        params.set("page", newPage);
        params.set("per_page", newPerPage);
        params.set("date", date);
        params.set("kode_poly", kodePoly);
        params.set("kode_dokter", kodeDokter);
        params.set("no_rm", noRM); // Update No RM in URL
        window.history.replaceState(null, "", `?${params.toString()}`);

        setPage(newPage);
        setPerPage(newPerPage);
        fetchDataPasienRujukan(newPage, newPerPage);
    };

    useEffect(() => {
        fetchDataPasienRujukan();
    }, []);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-lg text-gray-800 leading-tight">
                    List Kunjungan Pasien Rujukan
                </p>
            }
        >
            <Head title="List Kunjungan Pasien Rujukan" />
            <Card title="Pasien Rawat Jalan" style={{ marginBottom: 5 }}>
                <Row gutter={16} style={{ marginBottom: 10 }}>
                    <Col span={3}>
                        <DatePicker
                            allowClear={false}
                            value={dayjs(date)}
                            onChange={(dateMoment, dateString) =>
                                setDate(dateString)
                            }
                            placeholder="Pilih tanggal"
                            disabledDate={(current) =>
                                current && current > moment().endOf("day")
                            }
                        />
                    </Col>
                    <Col span={3}>
                        <Input
                            allowClear
                            placeholder="No RM"
                            value={noRM}
                            onChange={(e) => setNoRM(e.target.value)} // Add input for No RM
                        />
                    </Col>
                    <Col span={3}>
                        <Input
                            allowClear
                            placeholder="Kode Poli"
                            value={kodePoly}
                            onChange={(e) => setKodePoly(e.target.value)}
                        />
                    </Col>
                    <Col span={3}>
                        <Input
                            allowClear
                            placeholder="Kode Dokter"
                            value={kodeDokter}
                            onChange={(e) => setKodeDokter(e.target.value)}
                        />
                    </Col>
                    <Col span={2}>
                        <Button
                            block
                            type="primary"
                            onClick={() => {
                                const params = new URLSearchParams(
                                    window.location.search
                                );
                                params.set("page", 1);
                                params.set("per_page", perPage);
                                params.set("date", date);
                                params.set("kode_poly", kodePoly);
                                params.set("kode_dokter", kodeDokter);
                                params.set("no_rm", noRM); // Add No RM to the query params
                                window.history.replaceState(
                                    null,
                                    "",
                                    `?${params.toString()}`
                                );

                                setPage(1);
                                fetchDataPasienRujukan(1, perPage);
                            }}
                        >
                            Cari
                        </Button>
                    </Col>

                    <Col span={2}>
                        <Button
                            block
                            onClick={() => {
                                window.location.replace(
                                    `${route("rm.pasien-rujukan.list_rujukan")}`
                                );
                                return;
                            }}
                        >
                            Reset
                        </Button>
                    </Col>
                </Row>
                <small>
                    total data: {totalData}. Page: {page}. Perpage: {perPage}
                </small>

                <Table
                    dataSource={dataPasienRujukans}
                    columns={columnsRujukan}
                    size="small"
                    loading={loading}
                    rowKey="FRPNOTRANSAKSIKJ"
                    scroll={{ x: "max-content" }}
                    pagination={{
                        simple: true,
                        current: page,
                        total: totalData,
                        pageSize: perPage,
                    }}
                    onChange={handleTableChange}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
